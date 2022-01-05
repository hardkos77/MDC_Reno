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

namespace BorlabsCookie\Cookie;

use BorlabsCookie\Cookie\Backend\License;

class API
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private $apiURL = 'https://api.cookie.borlabs.io/v3';
    private $response = [];
    private $updateURL = 'https://update.borlabs.io/v2';

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
     * addVars function.
     *
     * @access public
     *
     * @param  mixed  $vars
     *
     * @return void
     */
    public function addVars($vars)
    {
        $vars[] = '__borlabsCookieCall';

        return $vars;
    }

    /**
     * detectRequests function.
     *
     * @access public
     * @return void
     */
    public function detectRequests()
    {
        global $wp;

        if (! empty($wp->query_vars['__borlabsCookieCall'])) {
            $data = json_decode(file_get_contents("php://input"));
            $this->handleRequest($wp->query_vars['__borlabsCookieCall'], $data);
            exit;
        }
    }

    /**
     * getLatestVersion function.
     *
     * @access public
     * @return void
     */
    public function getLatestVersion()
    {
        $licenseData = License::getInstance()->getLicenseData();

        $response = wp_remote_post(
            $this->updateURL . '/latest-version/' . (defined('BORLABS_COOKIE_DEV_BUILD')
            && BORLABS_COOKIE_DEV_BUILD == true ? 'dev-' : '') . BORLABS_COOKIE_SLUG,
            [
                'timeout' => 45,
                'body' => [
                    'version' => BORLABS_COOKIE_VERSION,
                    'product' => BORLABS_COOKIE_SLUG,
                    'php_version' => phpversion(), // Used to distinguish between >=7.4 and <7.4 builds
                    'licenseKey' => ! empty($licenseData->licenseKey) ? $licenseData->licenseKey : '',
                    'securityPatchesForExpiredLicenses' => ! License::getInstance()->isLicenseValid(),
                    'securityPatchesForTestEnvironmentLicenses' => ! empty(
                    Config::getInstance()->get(
                        'testEnvironment'
                    )
                    ) ? '1' : '0',
                    'debug_php_time' => date('Y-m-d H:i:s'),
                    'debug_php_timestamp' => time(),
                    'debug_timezone' => date_default_timezone_get(),
                ],
            ]
        );

        if (! empty($response) && is_array($response) && ! empty($response['body'])) {
            $body = json_decode($response['body']);

            if (! empty($body->success) && ! empty($body->updateInformation)) {
                return unserialize($body->updateInformation);
            }
        }
    }

    /**
     * getNews function.
     *
     * @access public
     * @return void
     */
    public function getNews()
    {
        $licenseData = License::getInstance()->getLicenseData();

        // Get latest news
        $response = $this->restPostRequest('/news', [
            'licenseKey' => ! empty($licenseData->licenseKey) ? $licenseData->licenseKey : '',
            'product' => BORLABS_COOKIE_SLUG,
            'version' => BORLABS_COOKIE_VERSION,
        ]);

        if (! empty($response->success)) {
            update_site_option('BorlabsCookieNews', $response->news);
            // Update last check
            update_site_option('BorlabsCookieNewsLastCheck', date('Ymd'), 'no');

            return (object) [
                'success' => true,
            ];
        } else {
            return $response;
        }
    }

    /**
     * getPluginInformation function.
     *
     * @access public
     * @return void
     */
    public function getPluginInformation()
    {
        $licenseData = License::getInstance()->getLicenseData();

        $response = wp_remote_post(
            $this->updateURL . '/plugin-information/' . (defined('BORLABS_COOKIE_DEV_BUILD')
            && BORLABS_COOKIE_DEV_BUILD == true ? 'dev-' : '') . BORLABS_COOKIE_SLUG,
            [
                'timeout' => 45,
                'body' => [
                    'version' => BORLABS_COOKIE_VERSION,
                    'product' => BORLABS_COOKIE_SLUG,
                    'php_version' => phpversion(), // Used to distinguish between >=7.4 and <7.4 builds
                    'licenseKey' => ! empty($licenseData->licenseKey) ? $licenseData->licenseKey : '',
                    'language' => get_locale(),
                ],
            ]
        );

        if (! empty($response) && is_array($response) && ! empty($response['body'])) {
            $body = json_decode($response['body']);

            if (! empty($body->success) && ! empty($body->pluginInformation)) {
                return unserialize($body->pluginInformation);
            }
        }
    }

    /**
     * handleRequest function.
     *
     * @access public
     *
     * @param  mixed  $call
     * @param  mixed  $token
     * @param  mixed  $data
     *
     * @return void
     */
    public function handleRequest($call, $data)
    {
        // Check if request is authorized
        if ($this->isAuthorized($data)) {
            if ($call === 'updateLicense') {
                $this->updateLicense($data);
            }
        } else {
            // cDC = crossDomainCookie
            if ($call === 'cDC') {
                Frontend\CrossDomainCookie::getInstance()->handleRequest($_GET);
            }
        }
    }

    /**
     * isAuthorized function.
     *
     * @access public
     *
     * @param  mixed  $data
     *
     * @return void
     */
    public function isAuthorized($data)
    {
        $isAuthorized = false;

        // Function getallheaders doesn't exist on FPM...
        $allHeaders = [];

        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $allHeaders[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))]
                    = $value;
            }
        }

        $hash = '';

        if (! empty($allHeaders['X-Borlabs-Cookie-Auth'])) {
            $hash = $allHeaders['X-Borlabs-Cookie-Auth'];
        }

        if (
            ! empty(License::getInstance()->getLicenseData()->salt)
            && HMAC::getInstance()->isValid(
                $data,
                License::getInstance()->getLicenseData()->salt,
                $hash
            )
        ) {
            $isAuthorized = true;
        }

        return $isAuthorized;
    }

    /**
     * registerLicense function.
     *
     * @access public
     *
     * @param  mixed  $licenseKey
     *
     * @return void
     */
    public function registerLicense($licenseKey)
    {
        $url = get_site_url();
        $urlWordPress = get_home_url();

        $licenseKey = trim($licenseKey);

        $data = [
            'licenseKey' => $licenseKey,
            'url' => $url,
            'networkUrl' => is_multisite() ? network_site_url() : '',
            'email' => '',
            'urlWordPress' => $url != $urlWordPress ? $urlWordPress : '',
            'version' => BORLABS_COOKIE_VERSION,
        ];

        // Register site
        $response = $this->restPostRequest('/register', $data);

        if (! empty($response->licenseKey)) {
            // Save license data
            License::getInstance()->saveLicenseData($response);

            return (object) [
                'success' => true,
                'successMessage' => _x(
                    'License registered successfully.',
                    'Backend / API / Alert Message',
                    'borlabs-cookie'
                ),
            ];
        } elseif (! empty($response->unlink)) {
            return $response;
        } else {
            return $response;
        }
    }

    /**
     * updateLicense function.
     *
     * @access public
     *
     * @param  mixed  $data
     *
     * @return void
     */
    public function updateLicense($data)
    {
        if (! empty($data->licenseKey)) {
            License::getInstance()->saveLicenseData($data);
        } elseif (! empty($data->removeLicense)) {
            License::getInstance()->removeLicense();
        }

        echo json_encode([
            'success' => true,
        ]);
    }

    /**
     * restPostRequest function.
     *
     * @access private
     *
     * @param  mixed  $route
     * @param  mixed  $data
     * @param  mixed  $salt  (default: null)
     *
     * @return void
     */
    private function restPostRequest($route, $data, $salt = null)
    {
        $args = [
            'timeout' => 45,
            'body' => $data,
        ];

        // Add authentification header
        if (! empty($salt)) {
            $args['headers'] = [
                'X-Borlabs-Cookie-Auth' => HMAC::getInstance()->hash($data, $salt),
            ];
        }

        // Make post request
        $response = wp_remote_post(
            $this->apiURL . $route,
            $args
        );

        if (
            ! empty($response) && is_array($response) && $response['response']['code'] == 200
            && ! empty($response['body'])
        ) {
            $responseBody = json_decode($response['body']);

            if (empty($responseBody->error)) {
                return $responseBody;
            } else {
                // Borlabs Cookie API messages
                $responseBody->errorMessage = $this->translateErrorCode(
                    $responseBody->errorCode,
                    $responseBody->message
                );

                return $responseBody;
            }
        } else {
            if (empty($response->errors) && ! empty($response['response']['message'])) {
                // Server message
                return (object) [
                    'errorMessage' => $response['response']['code'] . ' ' . $response['response']['message'],
                ];
            } else {
                // WP_Error messages
                return (object) [
                    'serverError' => true,
                    'errorMessage' => implode('<br>', $response->get_error_messages()),
                ];
            }
        }
    }

    /**
     * translateErrorCode function.
     *
     * @access private
     *
     * @param  mixed  $errorCode
     *
     * @return void
     */
    private function translateErrorCode($errorCode, $message = '')
    {
        $errorMessage = '';

        if ($errorCode == 'accessError') {
            $errorMessage = _x(
                'The request was blocked. Please try again later.',
                'Backend / API / Alert Message',
                'borlabs-cookie'
            );
        } elseif ($errorCode == 'unlinkRoutine') {
            $errorMessage = _x(
                'Your license key is already being used by another website. Please visit <a href="https://borlabs.io/account/" rel="nofollow noopener noreferrer" target="_blank">https://borlabs.io/account/</a> to remove the website from your license.',
                'Backend / API / Alert Message',
                'borlabs-cookie'
            );
        } elseif ($errorCode == 'validateHash') {
            $errorMessage = sprintf(
                _x(
                    'The request to the API could not be validated. %s',
                    'Backend / API / Alert Message',
                    'borlabs-cookie'
                ),
                $message
            );
        } elseif ($errorCode == 'invalidLicenseKey') {
            $errorMessage = _x('Your license key is not valid.', 'Backend / API / Alert Message', 'borlabs-cookie');
        } elseif ($errorCode == 'invalidMajorVersionLicenseKey') {
            $errorMessage = _x(
                'Your license key is not valid for this major version. Please upgrade your license key.',
                'Backend / API / Alert Message',
                'borlabs-cookie'
            );
        } else {
            // errorCode == error
            $errorMessage = sprintf(
                _x(
                    'An error occurred. Please contact the support. %s',
                    'Backend / API / Alert Message',
                    'borlabs-cookie'
                ),
                $message
            );
        }

        return $errorMessage;
    }
}
