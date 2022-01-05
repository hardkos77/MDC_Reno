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

namespace BorlabsCookie\Cookie\Frontend\Services;

class EzoicPreferences
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
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
     * getDefault function.
     *
     * @access public
     * @return void
     */
    public function getDefault()
    {
        $data = [
            'cookieId' => 'ezoic-preferences',
            'service' => 'EzoicPreferences',
            'name' => 'Ezoic - Preferences',
            'provider' => 'Ezoic Inc.',
            'purpose' => _x(
                'Remember information that changes the behavior or appearance of the site, such as your preferred language or the region in which you are located.',
                'Frontend / Cookie / Ezoic - Preferences / Text',
                'borlabs-cookie'
            ),
            'privacyPolicyURL' => _x(
                'https://www.ezoic.com/privacy-policy/',
                'Frontend / Cookie / Ezoic - Preferences / Text',
                'borlabs-cookie'
            ),
            'hosts' => [],
            'cookieName' => 'ez*, sitespeed_preview, FTNT*, SITESERVER, SL*, speed_no_process, GED_PLAYLIST_ACTIVITY, __guid',
            'cookieExpiry' => _x('1 Year', 'Frontend / Cookie / Ezoic - Preferences / Text', 'borlabs-cookie'),
            'optInJS' => $this->optInJS(),
            'optOutJS' => $this->optOutJS(),
            'fallbackJS' => '',
            'settings' => [
                'blockCookiesBeforeConsent' => false,
                'prioritize' => true,
            ],
            'status' => true,
            'undeletetable' => false,
        ];

        return $data;
    }

    /**
     * optInJS function.
     *
     * @access private
     * @return void
     */
    private function optInJS()
    {
        $code = <<<EOT
<script>
if (typeof window.BorlabsEZConsentCategories == 'object') {
    window.BorlabsEZConsentCategories.preferences = true;
}
</script>
EOT;

        return $code;
    }

    /**
     * optOutJS function.
     *
     * @access private
     * @return void
     */
    private function optOutJS()
    {
        $code = <<<EOT
<script>
if (typeof window.BorlabsEZConsentCategories == 'object') {
    window.BorlabsEZConsentCategories.preferences = false;
}
</script>
EOT;

        return $code;
    }
}
