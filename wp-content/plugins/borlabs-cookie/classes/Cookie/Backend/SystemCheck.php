<?php
/*
 * ----------------------------------------------------------------------
 *
 *                          Borlabs Cookie
 *                      developed by Borlabs
 *
 * ----------------------------------------------------------------------
 *
 * Copyright 2018-2021 Borlabs - Benjamin A. Bornschein. All rights reserved.
 * This file may not be redistributed in whole or significant part.
 * Content of this file is protected by international copyright laws.
 *
 * ----------------- Borlabs Cookie IS NOT FREE SOFTWARE -----------------
 *
 * @copyright Borlabs - Benjamin A. Bornschein, https://borlabs.io
 * @author Benjamin A. Bornschein, Borlabs ben@borlabs.io
 *
 */

namespace BorlabsCookie\Cookie\Backend;

use BorlabsCookie\Cookie\Config;
use BorlabsCookie\Cookie\Install;
use BorlabsCookie\Cookie\Multilanguage;

class SystemCheck
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    public $templatePath;
    private $messages = [];

    public function __construct()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    public function __clone()
    {
        trigger_error('Cloning is not allowed.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserialize is forbidden.', E_USER_ERROR);
    }

    /**
     * checkAndChangeCookieConsentLogIndex function.
     *
     * @access public
     * @return void
     */
    public function checkAndChangeCookieConsentLogIndex()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'borlabs_cookie_consent_log';

        if (Install::getInstance()->checkIfIndexExists($tableName, 'is_latest')) {
            // Remove key
            $wpdb->query(
                "
                ALTER TABLE
                    `" . $tableName . "`
                DROP INDEX
                    `is_latest`
            "
            );
        }

        // Add new key
        if (Install::getInstance()->checkIfIndexExists($tableName, 'uid') === false) {
            // Add key
            $wpdb->query(
                "
                ALTER TABLE
                    `" . $tableName . "`
                ADD KEY
                    `uid` (`uid`, `is_latest`)
            "
            );
        }
    }

    /**
     * checkAndChangeCookiesTable function.
     *
     * @access public
     * @return void
     */
    public function checkAndChangeCookiesTable()
    {
        global $wpdb;

        $tableNameCookies = $wpdb->prefix . 'borlabs_cookie_cookies';

        $cookieNameColumnType = Install::getInstance()->checkTypeOfColumn($tableNameCookies, 'cookie_name', 'text');

        if ($cookieNameColumnType === false) {
            $wpdb->query(
                "
                ALTER TABLE
                    `" . $tableNameCookies . "`
                MODIFY
                    `cookie_name` TEXT NOT NULL
            "
            );
        }

        $cookieExpiryColumnType = Install::getInstance()->checkTypeOfColumn($tableNameCookies, 'cookie_expiry', 'text');

        if ($cookieExpiryColumnType === false) {
            $wpdb->query(
                "
                ALTER TABLE
                    `" . $tableNameCookies . "`
                MODIFY
                    `cookie_expiry` TEXT NOT NULL
            "
            );
        }
    }

    /**
     * checkAndFixScriptBlockerTable function.
     *
     * @access public
     * @return void
     */
    public function checkAndFixScriptBlockerTable()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableNameScriptBlocker = $wpdb->prefix
            . 'borlabs_cookie_script_blocker'; // ->prefix contains base_prefix + blog id

        // Check if Script Blocker table is wrong schema
        $columnStatus = Install::getInstance()->checkIfColumnExists($tableNameScriptBlocker, 'content_blocker_id');

        if ($columnStatus === true) {
            // Fix Script Blocker Table
            $wpdb->query("DROP TABLE IF EXISTS `" . $tableNameScriptBlocker . "`");

            $sqlCreateTableScriptBlocker = Install::getInstance()->getCreateTableStatementScriptBlocker(
                $tableNameScriptBlocker,
                $charsetCollate
            );

            $wpdb->query($sqlCreateTableScriptBlocker);
        }
    }

    /**
     * checkCacheFolders function.
     *
     * @access public
     * @return void
     */
    public function checkCacheFolders()
    {
        $data = [
            'success' => true,
            'message' => '',
        ];

        // Check if cache folder exists
        if (! file_exists(WP_CONTENT_DIR . '/cache')) {
            if (! is_writable(WP_CONTENT_DIR)) {
                $data['success'] = false;
                $data['message'] = sprintf(
                    _x(
                        'The folder <strong>/%s</strong> is not writable. Please set the right permissions. See <a href="https://borlabs.io/folder-permissions/" rel="nofollow noopener noreferrer" target="_blank">FAQ</a>.',
                        'Backend / System Check / Alert Message',
                        'borlabs-cookie'
                    ),
                    basename(WP_CONTENT_DIR)
                );
            } else {
                mkdir(WP_CONTENT_DIR . '/cache');
                mkdir(WP_CONTENT_DIR . '/cache/borlabs-cookie');
            }
        }

        if (file_exists(WP_CONTENT_DIR . '/cache') && ! is_writable(WP_CONTENT_DIR . '/cache')) {
            $data['success'] = false;
            $data['message'] = sprintf(
                _x(
                    'The folder <strong>/%s/cache</strong> is not writable. Please set the right permissions. See <a href="https://borlabs.io/folder-permissions/" rel="nofollow noopener noreferrer" target="_blank">FAQ</a>.',
                    'Backend / System Check / Alert Message',
                    'borlabs-cookie'
                ),
                basename(WP_CONTENT_DIR)
            );
        }

        if (
            file_exists(WP_CONTENT_DIR . '/cache') && is_writable(WP_CONTENT_DIR . '/cache')
            && ! file_exists(
                WP_CONTENT_DIR . '/cache/borlabs-cookie'
            )
        ) {
            mkdir(WP_CONTENT_DIR . '/cache/borlabs-cookie');
        }

        if (
            file_exists(WP_CONTENT_DIR . '/cache/borlabs-cookie')
            && ! is_writable(
                WP_CONTENT_DIR . '/cache/borlabs-cookie'
            )
        ) {
            $data['success'] = false;
            $data['message'] = sprintf(
                _x(
                    'The folder <strong>/%s/cache/borlabs-cookie</strong> is not writable. Please set the right permissions. See <a href="https://borlabs.io/folder-permissions/" rel="nofollow noopener noreferrer" target="_blank">FAQ</a>.',
                    'Backend / System Check / Alert Message',
                    'borlabs-cookie'
                ),
                basename(WP_CONTENT_DIR)
            );
        }

        if (! file_exists(WP_CONTENT_DIR . '/cache/borlabs-cookie')) {
            $data['success'] = false;
            $data['message'] = sprintf(
                _x(
                    'The folder <strong>/%s/cache/borlabs-cookie</strong> does not exist. Please set the right permissions. See <a href="https://borlabs.io/folder-permissions/" rel="nofollow noopener noreferrer" target="_blank">FAQ</a>.',
                    'Backend / System Check / Alert Message',
                    'borlabs-cookie'
                ),
                basename(WP_CONTENT_DIR)
            );
        }

        return $data;
    }

    /**
     * checkDefaultContentBlocker function.
     *
     * @access public
     * @return void
     */
    public function checkDefaultContentBlocker()
    {
        global $wpdb;

        $data = [
            'success' => true,
            'message' => '',
        ];

        $tableName = $wpdb->prefix . 'borlabs_cookie_content_blocker';
        $sql = "
            SELECT
                `content_blocker_id`
            FROM
                `" . $tableName . "`
            WHERE
                `content_blocker_id` IN ('default', 'facebook', 'googlemaps', 'instagram', 'openstreetmap', 'twitter', 'vimeo', 'youtube')
                AND
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
        ";

        $defaultContentBlocker = $wpdb->get_results($sql);

        if (empty($defaultContentBlocker) || count($defaultContentBlocker) !== 8) {
            // Try to insert default entries
            ContentBlocker::getInstance()->resetDefault();

            // Check again
            $defaultContentBlocker = $wpdb->get_results($sql);

            if (empty($defaultContentBlocker) || count($defaultContentBlocker) !== 8) {
                $data = [
                    'success' => false,
                    'message' => sprintf(
                        _x(
                            'Could not insert default <strong>Content Blocker</strong>.',
                            'Backend / System Check / Alert Message',
                            'borlabs-cookie'
                        ),
                        $tableName
                    ),
                ];
            }
        }

        return $data;
    }

    /**
     * checkDefaultCookieGroups function.
     *
     * @access public
     * @return void
     */
    public function checkDefaultCookieGroups()
    {
        global $wpdb;

        $data = [
            'success' => true,
            'message' => '',
        ];

        $tableName = $wpdb->prefix . 'borlabs_cookie_groups';
        $sql = "
            SELECT
                `group_id`
            FROM
                `" . $tableName . "`
            WHERE
                `group_id` IN ('essential', 'statistics', 'marketing', 'external-media')
                AND
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
        ";

        $defaultCookieGroups = $wpdb->get_results($sql);

        if (empty($defaultCookieGroups) || count($defaultCookieGroups) !== 4) {
            // Try to insert default entries
            $wpdb->query(
                Install::getInstance()->getDefaultEntriesCookieGroups(
                    $tableName,
                    Multilanguage::getInstance()->getCurrentLanguageCode()
                )
            );

            // Check again
            $defaultCookieGroups = $wpdb->get_results($sql);

            if (empty($defaultCookieGroups) || count($defaultCookieGroups) !== 4) {
                $data = [
                    'success' => false,
                    'message' => sprintf(
                        _x(
                            'Could not insert default <strong>Cookie Groups</strong>.',
                            'Backend / System Check / Alert Message',
                            'borlabs-cookie'
                        ),
                        $tableName
                    ),
                ];
            }
        }

        // Change status of essential cookie group "essential"
        $wpdb->query(
            "
            UPDATE
                `" . $tableName . "`
            SET
                `status` = 1
            WHERE
                `group_id` = 'essential'
                AND
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
        "
        );

        return $data;
    }

    /**
     * checkDefaultCookies function.
     *
     * @access public
     * @return void
     */
    public function checkDefaultCookies()
    {
        global $wpdb;

        $data = [
            'success' => true,
            'message' => '',
        ];

        $tableName = $wpdb->prefix . 'borlabs_cookie_cookies';
        $sql = "
            SELECT
                `cookie_id`
            FROM
                `" . $tableName . "`
            WHERE
                `cookie_id` IN ('borlabs-cookie')
                AND
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
        ";

        $defaultCookies = $wpdb->get_results($sql);

        if (empty($defaultCookies)) {
            // Try to insert default entries (if the result was empty, it was due a change of the language and in this case, we will add all default cookies)
            $wpdb->query(
                Install::getInstance()->getDefaultEntriesCookies(
                    $tableName,
                    Multilanguage::getInstance()->getCurrentLanguageCode(),
                    $wpdb->prefix . 'borlabs_cookie_groups'
                )
            );

            // Check again
            $defaultCookies = $wpdb->get_results($sql);

            if (empty($defaultCookies)) {
                $data = [
                    'success' => false,
                    'message' => sprintf(
                        _x(
                            'Could not insert default <strong>Cookies</strong>.',
                            'Backend / System Check / Alert Message',
                            'borlabs-cookie'
                        ),
                        $tableName
                    ),
                ];
            }
        }

        // Change status of essential cookie "borlabs-cookie"
        $wpdb->query(
            "
            UPDATE
                `" . $tableName . "`
            SET
                `status` = 1
            WHERE
                `cookie_id` = 'borlabs-cookie'
                AND
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
        "
        );

        return $data;
    }

    /**
     * checkLanguageSettings function.
     *
     * @access public
     * @return void
     */
    public function checkLanguageSettings()
    {
        $data = [
            'success' => true,
            'message' => '',
        ];

        $language = Multilanguage::getInstance()->getCurrentLanguageCode();

        if (empty($language)) {
            $data['success'] = false;
            $data['message'] = _x(
                'Your language configuration is broken. Disable all plugins except <strong>Borlabs Cookie</strong> until this message disappears. When you have found the plugin that is causing this error, check if an update is available and install it.',
                'Backend / System Check / Alert Message',
                'borlabs-cookie'
            );
        }

        return $data;
    }

    /**
     * checkSettings function.
     *
     * @access public
     * @return void
     */
    public function checkSSLSettings()
    {
        $data = [
            'success' => true,
            'message' => '',
        ];

        // Check if HTTPS settings are correct
        $contentURL = parse_url(WP_CONTENT_URL);

        if ($contentURL['scheme'] !== 'https') {
            if ((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $_SERVER['SERVER_PORT'] === '443') {
                $data['success'] = false;
                $data['message'] = _x(
                    'Your SSL configuration is not correct. Please go to <strong>Settings &gt; General</strong> and replace <strong><em>http://</em></strong> with <strong><em>https://</em></strong> in the settings <strong>WordPress Address (URL)</strong> and <strong>Site Address (URL)</strong>.',
                    'Backend / System Check / Alert Message',
                    'borlabs-cookie'
                );
                $data['message'] .= "<br>WP_CONTENT_URL: " . WP_CONTENT_URL;
                $data['message'] .= "<br>\$_SERVER['HTTPS']: " . $_SERVER['HTTPS'];
                $data['message'] .= "<br>\$_SERVER['SERVER_PORT']: " . $_SERVER['SERVER_PORT'];
            } else {
                $data['success'] = false;
                $data['message'] = _x(
                    'Your website is not using a SSL certification.',
                    'Backend / System Check / Alert Message',
                    'borlabs-cookie'
                );
            }
        }

        return $data;
    }

    /**
     * checkTable function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $sqlCreateStatement
     *
     * @return void
     */
    public function checkTable($tableName, $sqlCreateStatement)
    {
        global $wpdb;

        $data = [
            'success' => true,
            'message' => '',
        ];

        if (! Install::getInstance()->checkIfTableExists($tableName)) {
            // Try to install the table
            dbDelta($sqlCreateStatement);

            // Check again
            if (! Install::getInstance()->checkIfTableExists($tableName)) {
                $data = [
                    'success' => false,
                    'message' => sprintf(
                        _x(
                            'The table <strong>%s</strong> could not be created, please check your server error logs for more details.',
                            'Backend / System Check / Alert Message',
                            'borlabs-cookie'
                        ),
                        $tableName
                    ),
                ];
            }
        }

        return $data;
    }

    /**
     * checkTableContentBlocker function.
     *
     * @access public
     * @return void
     */
    public function checkTableContentBlocker()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'borlabs_cookie_content_blocker';

        $sqlCreateTable = Install::getInstance()->getCreateTableStatementContentBlocker($tableName, $charsetCollate);

        $data = $this->checkTable($tableName, $sqlCreateTable);

        return $data;
    }

    /**
     * checkTableCookieConsentLog function.
     *
     * @access public
     * @return void
     */
    public function checkTableCookieConsentLog()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'borlabs_cookie_consent_log';

        $sqlCreateTable = Install::getInstance()->getCreateTableStatementCookieConsentLog($tableName, $charsetCollate);

        $data = $this->checkTable($tableName, $sqlCreateTable);

        return $data;
    }

    /**
     * checkTableCookieGroups function.
     *
     * @access public
     * @return void
     */
    public function checkTableCookieGroups()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'borlabs_cookie_groups';

        $sqlCreateTable = Install::getInstance()->getCreateTableStatementCookieGroups($tableName, $charsetCollate);

        $data = $this->checkTable($tableName, $sqlCreateTable);

        return $data;
    }

    /**
     * checkTables function.
     *
     * @access public
     * @return void
     */
    public function checkTableCookies()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'borlabs_cookie_cookies';

        $sqlCreateTable = Install::getInstance()->getCreateTableStatementCookies($tableName, $charsetCollate);

        $data = $this->checkTable($tableName, $sqlCreateTable);

        return $data;
    }

    /**
     * checkTableScriptBlocker function.
     *
     * @access public
     * @return void
     */
    public function checkTableScriptBlocker()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'borlabs_cookie_script_blocker';

        $sqlCreateTable = Install::getInstance()->getCreateTableStatementScriptBlocker($tableName, $charsetCollate);

        $data = $this->checkTable($tableName, $sqlCreateTable);

        return $data;
    }

    /**
     * getConsentLogTableSize function.
     *
     * @access public
     * @return void
     */
    public function getConsentLogTableSize()
    {
        global $wpdb;

        $table = (Config::getInstance()->get('aggregateCookieConsent') ? $wpdb->base_prefix : $wpdb->prefix)
            . "borlabs_cookie_consent_log";

        $dbName = $wpdb->dbname;

        // HyperDB workaround
        if (empty($dbName) && defined('DB_NAME')) {
            $dbName = DB_NAME;
        }

        $consentLogTableSize = $wpdb->get_results(
            "
            SELECT
                round(((`data_length` + `index_length`) / 1024 / 1024), 2) `size_in_mb`
            FROM
                `information_schema`.`TABLES`
            WHERE
                `TABLE_SCHEMA` = '" . esc_sql($dbName) . "'
                AND
                `TABLE_NAME` = '" . $table . "'
        "
        );

        return ! empty($consentLogTableSize[0]->size_in_mb) ? $consentLogTableSize[0]->size_in_mb : 0;
    }

    /**
     * getTotalConsentLogs function.
     *
     * @access public
     * @return void
     */
    public function getTotalConsentLogs()
    {
        global $wpdb;

        $table = (Config::getInstance()->get('aggregateCookieConsent') ? $wpdb->base_prefix : $wpdb->prefix)
            . "borlabs_cookie_consent_log";

        $totalConsentLogs = $wpdb->get_results(
            "
            SELECT
                COUNT(*) as `total`
            FROM
                `" . $table . "`
        "
        );

        return ! empty($totalConsentLogs[0]->total) ? $totalConsentLogs[0]->total : 0;
    }
}
