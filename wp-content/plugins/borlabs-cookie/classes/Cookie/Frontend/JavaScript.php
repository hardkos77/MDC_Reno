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

namespace BorlabsCookie\Cookie\Frontend;

use BorlabsCookie\Cookie\Config;
use BorlabsCookie\Cookie\Multilanguage;

class JavaScript
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
     * contentBlocker
     *
     * (default value: [])
     *
     * @var mixed
     * @access private
     */
    private $contentBlocker = [];
    private $cookiePath = null;
    private $cookieVersion = null;
    /**
     * fallbackCode
     *
     * (default value: [])
     *
     * @var mixed
     * @access private
     */
    private $fallbackCode = [];

    public function __construct()
    {
        // Domain information for javascript cookie
        $siteURL = get_home_url();
        $siteURLInfo = parse_url($siteURL);
        $this->cookiePath = ! empty($siteURLInfo['path']) ? $siteURLInfo['path'] : "/";

        if (Config::getInstance()->get('automaticCookieDomainAndPath') === false) {
            $this->cookiePath = Config::getInstance()->get('cookiePath');
        }

        $this->cookieVersion = intval(get_site_option('BorlabsCookieCookieVersion', 1));
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
     * addContentBlocker function.
     *
     * @access public
     *
     * @param  mixed  $contentBlockerId
     * @param  string  $globalJS  (default: '')
     * @param  string  $initJS  (default: '')
     * @param  mixed  $settings  (default: [])
     *
     * @return void
     */
    public function addContentBlocker($contentBlockerId, $globalJS = '', $initJS = '', $settings = [])
    {
        $settings = apply_filters('borlabsCookie/contentBlocker/modify/settings/' . $contentBlockerId, $settings);

        $this->contentBlocker[$contentBlockerId] = [
            'contentBlockerId' => $contentBlockerId,
            'global' => $globalJS,
            'init' => $initJS,
            'settings' => $settings,
        ];

        return true;
    }

    /**
     * getContentBlockerScriptsData function.
     *
     * @access public
     * @return void
     */
    public function getContentBlockerScriptsData()
    {
        $js = 'var borlabsCookieContentBlocker = {';

        if (! empty($this->contentBlocker)) {
            foreach ($this->contentBlocker as $contentBlockerId => $data) {
                $js .= '"' . $contentBlockerId . '": {';
                $js .= '"id": "' . $contentBlockerId . '",';
                $js .= '"global": function (contentBlockerData) { ' . $data['global'] . ' },';
                $js .= '"init": function (el, contentBlockerData) { ' . $data['init'] . ' },';
                $js .= '"settings": ' . json_encode($data['settings']);
                $js .= '},';
            }

            $js = substr($js, 0, -1);
        }

        $js .= '};';

        return $js;
    }

    /**
     * registerFooter function.
     *
     * @access public
     * @return void
     */
    public function registerFooter()
    {
        wp_enqueue_script(
            'borlabs-cookie',
            BORLABS_COOKIE_PLUGIN_URL . 'assets/javascript/borlabs-cookie.min.js',
            [
                Config::getInstance()->get('jQueryHandle'),
            ],
            BORLABS_COOKIE_VERSION,
            true
        );

        $jsConfig = [
            'ajaxURL' => admin_url('admin-ajax.php'),
            'language' => Multilanguage::getInstance()->getCurrentLanguageCode(),

            'animation' => Config::getInstance()->get('cookieBoxAnimation'),
            'animationDelay' => Config::getInstance()->get('cookieBoxAnimationDelay'),
            'animationIn' => Config::getInstance()->get('cookieBoxAnimationIn'),
            'animationOut' => Config::getInstance()->get('cookieBoxAnimationOut'),
            'blockContent' => Config::getInstance()->get('cookieBoxBlocksContent'),
            'boxLayout' => str_replace(['-slim', '-advanced'], '', Config::getInstance()->get('cookieBoxLayout')),
            'boxLayoutAdvanced' => strpos(Config::getInstance()->get('cookieBoxLayout'), '-advanced') !== false ? true
                : false,

            'automaticCookieDomainAndPath' => Config::getInstance()->get('automaticCookieDomainAndPath'),
            'cookieDomain' => Config::getInstance()->get('cookieDomain'),
            'cookiePath' => $this->cookiePath,
            'cookieLifetime' => Config::getInstance()->get('cookieLifetime'),
            'cookieLifetimeEssentialOnly' => Config::getInstance()->get('cookieLifetimeEssentialOnly'),
            'crossDomainCookie' => Config::getInstance()->get('crossDomainCookie'),

            'cookieBeforeConsent' => Config::getInstance()->get('cookieBeforeConsent'),
            'cookiesForBots' => Config::getInstance()->get('cookiesForBots'),
            'cookieVersion' => $this->cookieVersion,
            'hideCookieBoxOnPages' => Config::getInstance()->get('hideCookieBoxOnPages'),
            'respectDoNotTrack' => Config::getInstance()->get('respectDoNotTrack'),
            'reloadAfterConsent' => Config::getInstance()->get('reloadAfterConsent'),
            'showCookieBox' => Config::getInstance()->get('showCookieBox'),
            'cookieBoxIntegration' => Config::getInstance()->get('cookieBoxIntegration'),

            'ignorePreSelectStatus' => Config::getInstance()->get('cookieBoxIgnorePreSelectStatus'),

            'cookies' => [],
        ];

        $allCookies = Cookies::getInstance()->getAllCookieGroups();
        $cookies = [];

        if (! empty($allCookies)) {
            foreach ($allCookies as $cookieGroupData) {
                // Add all cookie groups to the array which are needed by the JavaScript class
                $jsConfig['cookies'][$cookieGroupData->group_id] = [];

                if (! empty($cookieGroupData->cookies)) {
                    foreach ($cookieGroupData->cookies as $cookieData) {
                        // Add all cookies to the array which are needed by the JavaScript class
                        $jsConfig['cookies'][$cookieGroupData->group_id][] = $cookieData->cookie_id;

                        $cookieData = apply_filters(
                            'borlabsCookie/cookie/modify/code/' . $cookieData->cookie_id,
                            $cookieData
                        );

                        $cookies[$cookieGroupData->group_id][$cookieData->cookie_id] = [
                            'cookieNameList' => CookieBlocker::getInstance()->prepareCookieNamesList(
                                $cookieData->cookie_name
                            ),
                            'settings' => $cookieData->settings,
                        ];

                        if (
                            ! empty($cookieData->opt_in_js) || ! empty($cookieData->opt_out_js)
                            || ! empty($cookieData->fallback_js)
                        ) {
                            $cookies[$cookieGroupData->group_id][$cookieData->cookie_id]['optInJS']
                                = empty($cookieData->settings['prioritize']) ? base64_encode(
                                do_shortcode($cookieData->opt_in_js)
                            ) : '';
                            $cookies[$cookieGroupData->group_id][$cookieData->cookie_id]['optOutJS'] = base64_encode(
                                do_shortcode($cookieData->opt_out_js)
                            );
                        }
                    }
                }
            }
        }

        $jsConfig = apply_filters('borlabsCookie/settings', $jsConfig);

        wp_localize_script('borlabs-cookie', 'borlabsCookieConfig', $jsConfig);
        wp_localize_script('borlabs-cookie', 'borlabsCookieCookies', $cookies);

        $jsCode = 'document.addEventListener("DOMContentLoaded", function (e) {';
        $jsCode .= "\n" . $this->getContentBlockerScriptsData() . "\n";

        $jsCode .= <<<EOT
    var BorlabsCookieInitCheck = function () {

    if (typeof window.BorlabsCookie === "object" && typeof window.jQuery === "function") {

        if (typeof borlabsCookiePrioritized !== "object") {
            borlabsCookiePrioritized = { optInJS: {} };
        }

        window.BorlabsCookie.init(borlabsCookieConfig, borlabsCookieCookies, borlabsCookieContentBlocker, borlabsCookiePrioritized.optInJS);
    } else {
        window.setTimeout(BorlabsCookieInitCheck, 50);
    }
};

BorlabsCookieInitCheck();
EOT;
        $jsCode .= "});";

        wp_add_inline_script('borlabs-cookie', $jsCode, 'after');
    }

    /**
     * registerHead function.
     *
     * @access public
     * @return void
     */
    public function registerHead()
    {
        $allCookies = Cookies::getInstance()->getAllCookieGroups();
        $prioritizedCodes = [];

        if (! empty($allCookies)) {
            foreach ($allCookies as $cookieGroupData) {
                if (! empty($cookieGroupData->cookies)) {
                    foreach ($cookieGroupData->cookies as $cookieData) {
                        if (! empty($cookieData->opt_in_js) || ! empty($cookieData->fallback_js)) {
                            if (! empty($cookieData->settings['prioritize'])) {
                                $prioritizedCodes[$cookieGroupData->group_id][$cookieData->cookie_id] = base64_encode(
                                    do_shortcode($cookieData->opt_in_js)
                                );
                            }

                            $this->fallbackCode[$cookieGroupData->group_id][$cookieData->cookie_id] = do_shortcode(
                                $cookieData->fallback_js
                            );
                        }
                    }
                }
            }
        }

        if (! empty($prioritizedCodes)) {
            wp_enqueue_script(
                'borlabs-cookie-prioritize',
                BORLABS_COOKIE_PLUGIN_URL . 'assets/javascript/borlabs-cookie-prioritize.min.js',
                [],
                BORLABS_COOKIE_VERSION
            );

            wp_localize_script('borlabs-cookie-prioritize', 'borlabsCookiePrioritized', [
                'domain' => Config::getInstance()->get('cookieDomain'),
                'path' => $this->cookiePath,
                'version' => $this->cookieVersion,
                'bots' => Config::getInstance()->get('cookiesForBots'),
                'optInJS' => $prioritizedCodes,
            ]);
        }
    }

    /**
     * registerHeadFallback function.
     *
     * @access public
     * @return void
     */
    public function registerHeadFallback()
    {
        // Fallback code is always executed
        if (! empty($this->fallbackCode)) {
            foreach ($this->fallbackCode as $groupData) {
                foreach ($groupData as $cookieFallbackCode) {
                    echo $cookieFallbackCode;
                }
            }
        }
    }
}
