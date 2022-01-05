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

use BorlabsCookie\Cookie\API;
use BorlabsCookie\Cookie\Config;
use BorlabsCookie\Cookie\Tools;
use DateTime;

class License
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private $licenseData;
    private $validLicenseTypes
        = [
            'borlabs-cookie-legacy',
            'borlabs-cookie',
            'borlabs-cookie-personal',
            'borlabs-cookie-business',
            'borlabs-cookie-professional',
            'borlabs-cookie-agency',
            'borlabs-cookie-business-small',
            'borlabs-cookie-business-medium',
            'borlabs-cookie-agency-small',
        ];

    public function __construct()
    {
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
     * display function.
     *
     * @access public
     */
    public function display()
    {
        $action = false;

        if (! empty($_POST['action'])) {
            $action = $_POST['action'];
        }

        if ($action !== false) {
            // Register
            if ($action === 'register' && check_admin_referer('borlabs_cookie_license_register')) {
                $responseRegisterLicense = API::getInstance()->registerLicense($_POST['licenseKey']);

                if (! empty($responseRegisterLicense->successMessage)) {
                    Messages::getInstance()->add($responseRegisterLicense->successMessage, 'success');
                } else {
                    Messages::getInstance()->add($responseRegisterLicense->errorMessage, 'error');
                }
            }

            // Refresh
            if ($action === 'refresh' && check_admin_referer('borlabs_cookie_license_refresh')) {
                $this->refreshLicense();

                Messages::getInstance()->add(
                    _x(
                        'License information refreshed successfully.',
                        'Backend / License / Alert Message',
                        'borlabs-cookie'
                    ),
                    'success'
                );
            }

            // Remove
            if ($action === 'remove' && check_admin_referer('borlabs_cookie_license_remove')) {
                $this->removeLicense();

                Messages::getInstance()->add(
                    _x('License removed successfully.', 'Backend / License / Alert Message', 'borlabs-cookie'),
                    'success'
                );
            }

            // Test environment
            if ($action === 'save' && check_admin_referer('borlabs_cookie_license_test_environment')) {
                $this->save($_POST);

                Messages::getInstance()->add(
                    _x('Saved successfully.', 'Backend / Global / Alert Message', 'borlabs-cookie'),
                    'success'
                );
            }
        }

        $this->displayOverview();
    }

    /**
     * displayOverview function.
     *
     * @access public
     */
    public function displayOverview()
    {
        // Validate license
        $this->validateLicense();

        // License information
        $licenseData = $this->getLicenseData();
        $validUntil = ! empty($licenseData->validUntil) ? new DateTime($licenseData->validUntil) : null;
        $supportUntil = ! empty($licenseData->supportUntil) ? new DateTime($licenseData->supportUntil) : null;

        $licenseStatus = $this->isLicenseValid() ? 'valid' : 'expired';
        $licenseStatusMessage = $this->getLicenseMessageStatus($licenseStatus);
        $licenseTypeTitle = esc_html(
            ! empty($licenseData->licenseType) ? $this->getLicenseTypeTitle($licenseData->licenseType) : ''
        );
        $licenseValidUntil = ! empty($licenseData->validUntil) ? Tools::getInstance()->formatTimestamp(
            $validUntil->getTimestamp(),
            null,
            false
        ) : '';
        $licenseSupportUntil = ! empty($licenseData->supportUntil) ? Tools::getInstance()->formatTimestamp(
            $supportUntil->getTimestamp(),
            null,
            false
        ) : '';
        $licenseMaxSites = ! empty($licenseData->maxSites) ? intval($licenseData->maxSites) : '';
        $licenseHideLicenseInformation = ! empty($licenseData->hideLicenseInformation) ? true : false;

        if (
            ! empty($licenseData->licenseType)
            && false === in_array($licenseData->licenseType, [
                'borlabs-cookie-personal',
                'borlabs-cookie-business',
                'borlabs-cookie-professional',
                'borlabs-cookie-agency',
                'borlabs-cookie-business-small',
                'borlabs-cookie-business-medium',
                'borlabs-cookie-agency-small',
            ])
        ) {
            if (! empty($licenseMaxSites)) {
                $licenseMaxSites .= " Multisite";
            }
        }

        // Mask license key
        $inputLicenseKey = preg_replace('/[^\-]/', '*', sanitize_text_field($this->getLicenseKey()));

        $inputTestEnvironment = ! empty(Config::getInstance()->get('testEnvironment')) ? 1 : 0;
        $switchTestEnvironment = $inputTestEnvironment ? ' active' : '';

        // Show information how to get the license key
        if ($this->isPluginUnlocked() === false) {
            Messages::getInstance()->add(
                _x(
                    'You can not find your license key? No problem! <a href="https://borlabs.io/account/?utm_source=Borlabs+Cookie&amp;utm_medium=Account+Link&amp;utm_campaign=Analysis" rel="nofollow noopener noreferrer" target="_blank">Click here</a> and log in to your account to get your license key.',
                    'Backend / License / Alert Message',
                    'borlabs-cookie'
                ),
                'info'
            );
        }

        include Backend::getInstance()->templatePath . '/license.html.php';
    }

    /**
     * getLicenseData function.
     *
     * @access public
     */
    public function getLicenseData()
    {
        if (empty($this->licenseData)) {
            // Such license system, much secure, wow.
            // Just kidding, you want all the trouble with updates, just to save some bucks?
            // Please support an independent developer and buy a license, thank you :)
            $licenseDataNetwork = get_site_option('BorlabsCookieLicenseData');
            $licenseDataBlog = get_option('BorlabsCookieLicenseData');

            if (! empty($licenseDataBlog)) {
                $licenseData = $licenseDataBlog;
            } else {
                $licenseData = $licenseDataNetwork;
            }

            if (! empty($licenseData)) {
                $this->licenseData = unserialize(base64_decode($licenseData));
            } else {
                $this->licenseData = (object) ['noLicense' => true];
            }
        }

        return $this->licenseData;
    }

    /**
     * getLicenseKey function.
     *
     * @access public
     */
    public function getLicenseKey()
    {
        $licenseKey = '';
        $licenseKeyNetwork = get_site_option('BorlabsCookieLicenseKey');
        $licenseKeyBlog = get_option('BorlabsCookieLicenseKey');

        if (! empty($licenseKeyNetwork)) {
            $licenseKey = $licenseKeyNetwork;
        }

        if (! empty($licenseKeyBlog)) {
            $licenseKey = $licenseKeyBlog;
        }

        return $licenseKey;
    }

    /**
     * getLicenseMessageActivateKey function.
     *
     * @access public
     */
    public function getLicenseMessageActivateKey()
    {
        $html = '<div class="alert alert-info" role="alert">';
        $html .= _x(
            'Please activate your license key first. <a href="?page=borlabs-cookie-license">Click here</a> to enter your license key.',
            'Backend / License / Alert Message',
            'borlabs-cookie'
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * getLicenseMessageEnterKey function.
     *
     * @access public
     */
    public function getLicenseMessageEnterKey()
    {
        return _ex(
            'Please enter your license key to receive updates.',
            'Backend / License / Alert Message',
            'borlabs-cookie'
        );
    }

    /**
     * getLicenseMessageKeyExpired function.
     *
     * @access public
     */
    public function getLicenseMessageKeyExpired()
    {
        return _x(
            'Please renew your license key to receive updates. <a href="https://borlabs.io/account/" target="_blank" rel="nofollow noopener noreferrer">Click here</a> to log into your account and purchase a license renewal.',
            'Backend / License / Alert Message',
            'borlabs-cookie'
        );
    }

    /**
     * getLicenseMessageStatus function.
     *
     * @access public
     *
     * @param  mixed  $status
     *
     */
    public function getLicenseMessageStatus($status)
    {
        $message = '';

        if ($status === 'valid') {
            $message = _x('Your license is valid.', 'Backend / License / Text', 'borlabs-cookie');
        } elseif ($status === 'expired') {
            $message = _x('Your license has expired.', 'Backend / License / Text', 'borlabs-cookie');
        }

        return $message;
    }

    /**
     * getLicenseTypeTitle function.
     *
     * @access public
     *
     * @param  mixed  $licenseType
     *
     */
    public function getLicenseTypeTitle($licenseType)
    {
        $licenseType = strtolower($licenseType);

        switch ($licenseType) {
            case 'borlabs-cookie-personal':
                $licenseType = _x('Personal', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-business':
                $licenseType = _x('Business', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-professional':
                $licenseType = _x('Professional', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-agency':
                $licenseType = _x('Agency', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-legacy':
                $licenseType = _x('Agency (Legacy)', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-business-small':
                $licenseType = _x('Business - Small', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-business-medium':
                $licenseType = _x('Business - Medium', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie-agency-small':
                $licenseType = _x('Agency - Small', 'Backend / License / Text', 'borlabs-cookie');
                break;
            case 'borlabs-cookie':
            default:
                $licenseType = _x('Classic (Legacy)', 'Backend / License / Text', 'borlabs-cookie');
        }

        return $licenseType;
    }

    /**
     * handleLicenseExpiredMessage function.
     *
     * @access public
     */
    public function handleLicenseExpiredMessage()
    {
        if (! empty($this->getLicenseData()->validUntil) && $this->isLicenseValid() === false) {
            // Try to re-validate
            $this->validateLicense();
            $this->getLicenseData();

            if ($this->isLicenseValid() === false) {
                Messages::getInstance()->add($this->getLicenseMessageKeyExpired(), 'error');
            }
        }
    }

    /**
     * isLicenseValid function.
     *
     * @access public
     */
    public function isLicenseValid()
    {
        // Such license system, much secure, wow.
        // Just kidding, you want all the trouble with updates, just to save some bucks?
        // Please support an independent developer and buy a license, thank you :)
        $isValid = false;

        if (! empty($this->getLicenseData()->validUntil)) {
            if ($this->getLicenseData()->validUntil >= date('Y-m-d')) {
                $isValid = true;
            }
        }

        return true;
    }

    /**
     * isPluginUnlocked function.
     *
     * @access public
     */
    public function isPluginUnlocked()
    {
        // Such license system, much secure, wow.
        // Just kidding, you want all the trouble with updates, just to save some bucks?
        // Please support an independent developer and buy a license, thank you :)
        $unlocked = false;

        if (! empty($this->getLicenseData()->licenseType)) {
            if (in_array($this->getLicenseData()->licenseType, $this->validLicenseTypes)) {
                $unlocked = true;
            }
        }

        // If installation is in a test environment
        if (Config::getInstance()->get('testEnvironment')) {
            $unlocked = true;
        }

        return true;
    }

    /**
     * refreshLicense function.
     *
     * @access public
     */
    public function refreshLicense()
    {
        $licenseKey = $this->getLicenseKey();

        if (! empty($licenseKey)) {
            $responseRegisterLicense = API::getInstance()->registerLicense($licenseKey);
        }
    }

    /**
     * removeLicense function.
     *
     * @access public
     */
    public function removeLicense()
    {
        // Check if blog or network key should be removed
        $licenseKeyNetwork = get_site_option('BorlabsCookieLicenseKey');
        $licenseKeyBlog = get_option('BorlabsCookieLicenseKey');

        if (! empty($licenseKeyBlog)) {
            delete_option('BorlabsCookieLicenseData');
            delete_option('BorlabsCookieLicenseKey');
            delete_option('BorlabsCookieUnlinkData');
        } elseif (! empty($licenseKeyNetwork)) {
            delete_site_option('BorlabsCookieLicenseData');
            delete_site_option('BorlabsCookieLicenseKey');
            delete_site_option('BorlabsCookieUnlinkData');
        }

        // Set property to null
        $this->licenseData = null;

        // getLicenseData is now able to set the correct information for licenseData
        $this->getLicenseData();
    }

    /**
     * save function.
     *
     * @access public
     *
     * @param  mixed  $formData
     */
    public function save($formData)
    {
        $updatedConfig = Config::getInstance()->get();

        $updatedConfig['testEnvironment'] = ! empty($formData['testEnvironment']) ? true : false;

        // Save config
        Config::getInstance()->saveConfig($updatedConfig);
    }

    /**
     * saveLicenseData function.
     *
     * @access public
     *
     * @param  mixed  $licenseData
     */
    public function saveLicenseData($licenseData)
    {
        if (! empty($licenseData->licenseKey)) {
            if (
                in_array($licenseData->licenseType, $this->validLicenseTypes)
                && $licenseData->licenseType !== 'borlabs-cookie-legacy'
            ) {
                update_option('BorlabsCookieLicenseData', base64_encode(serialize($licenseData)), 'no');
                update_option('BorlabsCookieLicenseKey', $licenseData->licenseKey, 'no');
            } else {
                update_site_option('BorlabsCookieLicenseData', base64_encode(serialize($licenseData)), 'no');
                update_site_option('BorlabsCookieLicenseKey', $licenseData->licenseKey, 'no');
            }

            $this->licenseData = $licenseData;
        }
    }

    /**
     * validateLicense function.
     *
     * @access public
     */
    public function validateLicense()
    {
        $lastCheck = intval(get_option('BorlabsCookieLicenseLastCheck', 0));
        $licenseKey = $this->getLicenseKey();

        if (
            ! empty($licenseKey)
            && (empty($lastCheck)
                || $lastCheck < intval(
                    date('Ymd', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 3))
                ))
        ) {
            $responseRegisterLicense = API::getInstance()->registerLicense($licenseKey);

            if (empty($responseRegisterLicense->successMessage)) {
                if (empty($responseRegisterLicense->serverError)) {
                    $this->removeLicense();
                }
            } else {
                // Update last check
                update_option('BorlabsCookieLicenseLastCheck', date('Ymd'), 'no');
            }
        }
    }
}
