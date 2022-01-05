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

namespace BorlabsCookie\Cookie\Frontend\ThirdParty\Plugins;

use BorlabsCookie\Cookie\Frontend\Cookies;

class PixelYourSite
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
     * register function.
     *
     * @access public
     * @return void
     */
    public function register()
    {
        add_filter('pys_disable_facebook_by_gdpr', static function () {
            return ! Cookies::getInstance()->checkConsent('facebook-pixel');
        });
        add_filter('pys_disable_analytics_by_gdpr', static function () {
            return ! Cookies::getInstance()->checkConsent('google-analytics');
        });
        add_filter('pys_disable_google_ads_by_gdpr', static function () {
            return ! Cookies::getInstance()->checkConsent('google-adsense');
        });
        add_filter('pys_disable_pinterest_by_gdpr', static function () {
            return ! Cookies::getInstance()->checkConsent('pinterest');
        });
        add_filter('pys_disable_bing_by_gdpr', static function () {
            return ! Cookies::getInstance()->checkConsent('bing');
        });
    }
}
