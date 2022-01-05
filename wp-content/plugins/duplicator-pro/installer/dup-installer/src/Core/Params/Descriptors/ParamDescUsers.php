<?php

/**
 * Users params descriptions
 *
 * @category  Duplicator
 * @package   Installer
 * @author    Snapcreek <admin@snapcreek.com>
 * @copyright 2011-2021  Snapcreek LLC
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 */

namespace Duplicator\Installer\Core\Params\Descriptors;

use Duplicator\Installer\Core\Params\PrmMng;
use Duplicator\Installer\Core\Params\Items\ParamItem;
use Duplicator\Installer\Core\Params\Items\ParamForm;
use Duplicator\Installer\Core\Params\Items\ParamOption;
use Duplicator\Installer\Core\Params\Items\ParamFormUsersReset;
use DUPX_DBInstall;
use DUPX_InstallerState;
use DUPX_MU;

/**
 * class where all parameters are initialized. Used by the param manager
 */
final class ParamDescUsers implements DescriptorInterface
{
    const USER_MODE_OVERWRITE    = 'overwrite';
    const USER_MODE_KEEP_USERS   = 'keep_users';
    const USER_MODE_IMPORT_USERS = 'import_users';
    /**
     * Init params
     *
     * @param ParamItem[]|ParamForm[] $params params list
     *
     * @return void
     */
    public static function init(&$params)
    {
        $params[PrmMng::PARAM_USERS_MODE] = new ParamForm(
            PrmMng::PARAM_USERS_MODE,
            ParamForm::TYPE_STRING,
            ParamForm::FORM_TYPE_RADIO,
            array(
                'default'          => self::USER_MODE_OVERWRITE,
                'sanitizeCallback' => function ($value) {
                    if (
                        DUPX_InstallerState::getInstance()->getMode() !== DUPX_InstallerState::MODE_OVR_INSTALL ||
                        DUPX_InstallerState::isRestoreBackup() ||
                        DUPX_InstallerState::isAddSiteOnMultisite()
                    ) {
                        // if is restore backup user mode must be overwrite
                        return ParamDescUsers::USER_MODE_OVERWRITE;
                    }

                    $overwriteData = PrmMng::getInstance()->getValue(PrmMng::PARAM_OVERWRITE_SITE_DATA);
                    if ($overwriteData['isMultisite']) {
                        return ParamDescUsers::USER_MODE_OVERWRITE;
                    }

                    // disable keep users for some db actions
                    switch (PrmMng::getInstance()->getValue(PrmMng::PARAM_DB_ACTION)) {
                        case DUPX_DBInstall::DBACTION_CREATE:
                        case DUPX_DBInstall::DBACTION_MANUAL:
                        case DUPX_DBInstall::DBACTION_ONLY_CONNECT:
                            return ParamDescUsers::USER_MODE_OVERWRITE;
                        case DUPX_DBInstall::DBACTION_EMPTY:
                        case DUPX_DBInstall::DBACTION_REMOVE_ONLY_TABLES:
                        case DUPX_DBInstall::DBACTION_RENAME:
                            return $value;
                    }
                },
                'acceptValues' => array(
                    self::USER_MODE_OVERWRITE,
                    self::USER_MODE_KEEP_USERS,
                    self::USER_MODE_IMPORT_USERS
                )
            ),
            array(
                'status' => function () {
                    /** Hide user mode instandalone migration for now */
                    return ParamForm::STATUS_SKIP;
                    if (DUPX_InstallerState::getInstance()->getMode() !== DUPX_InstallerState::MODE_OVR_INSTALL) {
                        return ParamForm::STATUS_DISABLED;
                    }

                    $overwriteData = PrmMng::getInstance()->getValue(PrmMng::PARAM_OVERWRITE_SITE_DATA);

                    if (
                        $overwriteData['isMultisite'] ||
                        DUPX_InstallerState::isRestoreBackup() ||
                        DUPX_InstallerState::isAddSiteOnMultisite() ||
                        DUPX_MU::newSiteIsMultisite()
                    ) {
                        return ParamForm::STATUS_DISABLED;
                    }
                    return ParamForm::STATUS_ENABLED;
                },
                'label'   => 'Users:',
                'options' => function ($item) {
                    $result   = array();
                    $result[] = new ParamOption(ParamDescUsers::USER_MODE_OVERWRITE, 'Overwrite');
                    $result[] = new ParamOption(
                        ParamDescUsers::USER_MODE_KEEP_USERS,
                        'Keep',
                        function () {
                            if (count(ParamDescUsers::getKeepUsersByParams()) === 0) {
                                return ParamOption::OPT_HIDDEN;
                            }
                            return ParamOption::OPT_ENABLED;
                        }
                    );
                    $result[] = new ParamOption(ParamDescUsers::USER_MODE_IMPORT_USERS, 'Merge');
                    return $result;
                },
                'inlineHelp' => dupxTplRender('parts/params/inline_helps/user_mode', array(), false),
                'wrapperClasses' => array('revalidate-on-change')
            )
        );

        $params[PrmMng::PARAM_ADD_SUBSITE_USER_MODE] = new ParamForm(
            PrmMng::PARAM_ADD_SUBSITE_USER_MODE,
            ParamForm::TYPE_STRING,
            ParamForm::FORM_TYPE_RADIO,
            array(
                'default'          => ParamDescUsers::USER_MODE_IMPORT_USERS,
                'sanitizeCallback' => function ($value) {
                    if (!DUPX_InstallerState::isAddSiteOnMultisite()) {
                        // if is restore backup user mode must be overwrite
                        return ParamDescUsers::USER_MODE_IMPORT_USERS;
                    } else {
                        return $value;
                    }
                },
                'acceptValues' => array(
                    ParamDescUsers::USER_MODE_KEEP_USERS,
                    ParamDescUsers::USER_MODE_IMPORT_USERS
                )
            ),
            array(
                'status' => function () {
                    if (!DUPX_InstallerState::isAddSiteOnMultisiteAvaiable()) {
                        return ParamForm::STATUS_SKIP;
                    }

                    if (DUPX_InstallerState::isAddSiteOnMultisite()) {
                        return ParamForm::STATUS_ENABLED;
                    } else {
                        return ParamForm::STATUS_DISABLED;
                    }
                },
                'label'   => 'Users:',
                'options' => function ($item) {
                    $result   = array();
                    $result[] = new ParamOption(ParamDescUsers::USER_MODE_IMPORT_USERS, 'Import');
                    $result[] = new ParamOption(
                        ParamDescUsers::USER_MODE_KEEP_USERS,
                        'Ignore',
                        function () {
                            if (count(ParamDescUsers::getContentOwnerUsres()) === 0) {
                                return ParamForm::STATUS_DISABLED;
                            } else {
                                return ParamForm::STATUS_ENABLED;
                            }
                        }
                    );
                    return $result;
                },
                'inlineHelpTitle' => 'User Mode',
                'inlineHelp' => dupxTplRender('parts/params/inline_helps/subsite_user_mode', array(), false),
                'wrapperClasses' => array('revalidate-on-change')
            )
        );

        $params[PrmMng::PARAM_KEEP_TARGET_SITE_USERS] = new ParamForm(
            PrmMng::PARAM_KEEP_TARGET_SITE_USERS,
            ParamForm::TYPE_INT,
            ParamForm::FORM_TYPE_SELECT,
            array(
                'default'          => 0,
                'sanitizeCallback' => function ($value) {
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_USERS_MODE) != ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return 0;
                    }
                    return (int) $value;
                },
                'validateCallback' => function ($value) {
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_USERS_MODE) != ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return true;
                    }
                    if ($value == 0) {
                        return false;
                    }
                    foreach (ParamDescUsers::getKeepUsersByParams() as $user) {
                        if ($value == $user['id']) {
                            return true;
                        }
                    }
                    return false;
                }
            ),
            array(
                'status' => function () {
                    /** Hide user mode instandalone migration for now */
                    return ParamForm::STATUS_SKIP;
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_USERS_MODE) === ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return ParamForm::STATUS_ENABLED;
                    } else {
                        return ParamForm::STATUS_DISABLED;
                    }
                },
                'label'   => 'Content Author:',
                'options' => function ($item) {
                    $result   = array();
                    $result[] = new ParamOption(0, ' - SELECT - ');
                    foreach (ParamDescUsers::getKeepUsersByParams() as $userData) {
                        $result[] = new ParamOption($userData['id'], '[' . $userData['id'] . '] ' . $userData['user_login']);
                    }
                    return $result;
                },
                'wrapperClasses' => array('revalidate-on-change'),
                'subNote'        => 'Keep users of the current site and eliminate users of the original site.<br>' .
                                    '<b>Assigns all pages, posts, media and custom post types to the selected user.</b>'
            )
        );

        $params[PrmMng::PARAM_CONTENT_OWNER] = new ParamForm(
            PrmMng::PARAM_CONTENT_OWNER,
            ParamForm::TYPE_INT,
            ParamForm::FORM_TYPE_SELECT,
            array(
                'default'          => 0,
                'sanitizeCallback' => function ($value) {
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_ADD_SUBSITE_USER_MODE) != ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return 0;
                    }
                    return (int) $value;
                },
                'validateCallback' => function ($value) {
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_ADD_SUBSITE_USER_MODE) != ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return true;
                    }
                    if ($value == 0) {
                        return false;
                    }
                    foreach (ParamDescUsers::getContentOwnerUsres() as $user) {
                        if ($value == $user['id']) {
                            return true;
                        }
                    }
                    return false;
                },
                'invalidMessage' => "When importing into a multisite you must select a user from the multisite that will own " .
                                    "all the posts and pages of the imported site."
            ),
            array(
                'status' => function () {
                    if (PrmMng::getInstance()->getValue(PrmMng::PARAM_ADD_SUBSITE_USER_MODE) === ParamDescUsers::USER_MODE_KEEP_USERS) {
                        return ParamForm::STATUS_ENABLED;
                    } else {
                        return ParamForm::STATUS_DISABLED;
                    }
                },
                'label'   => 'Content Author:',
                'options' => function ($item) {
                    $result = array();
                    $result[] = new ParamOption(0, ' - SELECT - ');
                    foreach (ParamDescUsers::getContentOwnerUsres() as $userData) {
                        $result[] = new ParamOption($userData['id'], '[' . $userData['id'] . '] ' . $userData['user_login']);
                    }
                    return $result;
                },
                'wrapperClasses' => array('revalidate-on-change'),
                'subNote'        => '<b>Author of all imported pages, posts, media and custom post types will be set to this user.</b><br>' .
                                    'All users of impoted site will be eliminated.</b>'
            )
        );

        $params[PrmMng::PARAM_USERS_PWD_RESET] = new ParamFormUsersReset(
            PrmMng::PARAM_USERS_PWD_RESET,
            ParamFormUsersReset::TYPE_ARRAY_STRING,
            ParamFormUsersReset::FORM_TYPE_USERS_PWD_RESET,
            array( // ITEM ATTRIBUTES
                'default' => array_map(function ($value) {
                    return '';
                }, \DUPX_ArchiveConfig::getInstance()->getUsersLists()),
                'sanitizeCallback' => array('\\Duplicator\\Libs\\Snap\\SnapUtil', 'sanitizeNSCharsNewlineTrim'),
                'validateCallback' => function ($value) {
                    return strlen($value) == 0 || strlen($value) >= \DUPX_Constants::MIN_NEW_PASSWORD_LEN;
                },
                'invalidMessage' => 'can\'t have less than ' . \DUPX_Constants::MIN_NEW_PASSWORD_LEN . ' characters'
            ),
            array( // FORM ATTRIBUTES
                'status' => function ($paramObj) {
                    if (ParamDescUsers::getUsersMode() != ParamDescUsers::USER_MODE_OVERWRITE) {
                        return ParamForm::STATUS_DISABLED;
                    } else {
                        return ParamForm::STATUS_ENABLED;
                    }
                },
                'label'       => 'Existing user reset password:',
                'classes'     => 'strength-pwd-check',
                'attr'        => array(
                    'title'       => \DUPX_Constants::MIN_NEW_PASSWORD_LEN . ' characters minimum',
                    'placeholder' => "Reset user password"
                )
            )
        );
    }

    /**
     *
     * @return array
     */
    public static function getKeepUsersByParams()
    {
        $overwriteData = PrmMng::getInstance()->getValue(PrmMng::PARAM_OVERWRITE_SITE_DATA);

        if (!empty($overwriteData['adminUsers'])) {
            return $overwriteData['adminUsers'];
        }

        return array();
    }

    /**
     *
     * @param null|int $subsiteId if null get current select subsite overwrite
     * @return array // restur list of content owner users
     */
    public static function getContentOwnerUsres($subsiteId = null)
    {
        $result        = array();
        $overwriteData = PrmMng::getInstance()->getValue(PrmMng::PARAM_OVERWRITE_SITE_DATA);

        if (is_null($subsiteId)) {
            $owrIdId = PrmMng::getInstance()->getValue(PrmMng::PARAM_SUBSITE_OVERWRITE_ID);
        } else {
            $owrIdId = $subsiteId;
        }

        if ($owrIdId > 0 && !empty($overwriteData['subsites'])) {
            foreach ($overwriteData['subsites'] as $subsite) {
                if ($subsite['id'] == $owrIdId) {
                    $result = $subsite['adminUsers'];
                    break;
                }
            }
        }

        if (empty($result) && !empty($overwriteData['adminUsers'])) {
            $result = $overwriteData['adminUsers'];
        }

        if (isset($overwriteData['loggedUser'])) {
            // insert the logged in user always at the beginning of the array
            foreach ($result as $key => $user) {
                if ($user['id'] == $overwriteData['loggedUser']['id']) {
                    unset($result[$key]);
                    break;
                }
            }
            array_unshift($result, $overwriteData['loggedUser']);
        }

        return $result;
    }

    /**
     * Return import users mode
     *
     * @return string
     */
    public static function getUsersMode()
    {
        $paramsManager = PrmMng::getInstance();

        if (DUPX_InstallerState::isAddSiteOnMultisite()) {
            return $paramsManager->getValue(PrmMng::PARAM_ADD_SUBSITE_USER_MODE);
        } else {
            return $paramsManager->getValue(PrmMng::PARAM_USERS_MODE);
        }
    }

    /**
     *
     * @return int
     */
    public static function getKeepUserId()
    {
        $paramsManager = PrmMng::getInstance();

        if (DUPX_InstallerState::isAddSiteOnMultisite()) {
            return $paramsManager->getValue(PrmMng::PARAM_CONTENT_OWNER);
        } else {
            return $paramsManager->getValue(PrmMng::PARAM_KEEP_TARGET_SITE_USERS);
        }
    }

    /**
     * Update params after overwrite logic
     *
     * @param ParamItem[]|ParamForm[] $params params list
     *
     * @return void
     */
    public static function updateParamsAfterOverwrite($params)
    {
    }
}
