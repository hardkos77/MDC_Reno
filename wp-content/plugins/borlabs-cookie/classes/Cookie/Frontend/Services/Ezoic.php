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

class Ezoic
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
            'cookieId' => 'ezoic',
            'service' => 'Ezoic',
            'name' => 'Ezoic',
            'provider' => 'Ezoic Inc.',
            'purpose' => _x(
                'Necessary for the basic functions of the website.',
                'Frontend / Cookie / Ezoic / Text',
                'borlabs-cookie'
            ),
            'privacyPolicyURL' => _x(
                'https://www.ezoic.com/privacy-policy/',
                'Frontend / Cookie / Ezoic / Text',
                'borlabs-cookie'
            ),
            'hosts' => [],
            'cookieName' => 'ez*, cf*, unique_id, __cf*, __utmt*',
            'cookieExpiry' => _x('1 Year', 'Frontend / Cookie / Ezoic / Text', 'borlabs-cookie'),
            'optInJS' => $this->optInJS(),
            'optOutJS' => '',
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
window.BorlabsEZConsentCategories = window.BorlabsEZConsentCategories || {};
window.BorlabsCookieEzoicHandle = function (e) {

    window.BorlabsEZConsentCategories.preferences = window.BorlabsEZConsentCategories.preferences || false;
    window.BorlabsEZConsentCategories.statistics = window.BorlabsEZConsentCategories.statistics || false;
    window.BorlabsEZConsentCategories.marketing = window.BorlabsEZConsentCategories.marketing || false;

    if (typeof BorlabsEZConsentCategories == 'object') {
        var waitForEzoic = function () {
			if (typeof __ezconsent == 'object') {
    			window.ezConsentCategories = window.BorlabsEZConsentCategories;
    			__ezconsent.setEzoicConsentSettings(window.ezConsentCategories);
			} else {
				window.setTimeout(waitForEzoic, 60);
			}
		};

		waitForEzoic();
    }
};

document.addEventListener("borlabs-cookie-prioritized-code-unblocked", window.BorlabsCookieEzoicHandle, false);
document.addEventListener("borlabs-cookie-code-unblocked-after-consent", window.BorlabsCookieEzoicHandle, false);
</script>
EOT;

        return $code;
    }
}
