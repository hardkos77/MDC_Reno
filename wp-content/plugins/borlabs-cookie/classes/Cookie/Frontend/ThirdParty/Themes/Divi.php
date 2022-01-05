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

namespace BorlabsCookie\Cookie\Frontend\ThirdParty\Themes;

use BorlabsCookie\Cookie\Frontend\ContentBlocker;
use BorlabsCookie\Cookie\Frontend\JavaScript;

class Divi
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
     * detectGoogleMaps function.
     *
     * @access public
     *
     * @param  mixed  $content
     *
     * @return void
     */
    public function detectGoogleMaps($content)
    {
        if (strpos($content, 'et_pb_map_container') !== false) {
            $googleApiSettings = get_option('et_google_api_settings');

            if (! empty($googleApiSettings['api_key'])) {
                // Get settings of the Content Blocker
                $contentBlockerData = ContentBlocker::getInstance()->getContentBlockerData('googlemaps');

                // Only modify when Google Maps Content Blocker is active
                if (! empty($contentBlockerData)) {
                    // Overwrite setting and always execute global code before unblocking the content
                    $contentBlockerData['settings']['executeGlobalCodeBeforeUnblocking'] = "1";

                    // Add updated settings, global js, and init js of the Content Blocker
                    JavaScript::getInstance()->addContentBlocker(
                        $contentBlockerData['content_blocker_id'],
                        $contentBlockerData['globalJS']
                        . ' jQuery("body").append("<" + "script type=\'text/javascript\' src=\'https://maps.googleapis.com/maps/api/js?v=3&#038;key='
                        . urlencode($googleApiSettings['api_key']) . '&#038;ver=' . ET_BUILDER_PRODUCT_VERSION
                        . '\'"+"><"+"/script>"); ',
                        $contentBlockerData['initJS']
                        . ' var borlabsDiviGoogleMaps = setInterval(function () { if (typeof google !== "undefined" && typeof google.maps !== "undefined") { clearInterval(borlabsDiviGoogleMaps); jQuery(".et_pb_map_container").each(function () { if (jQuery(this).children(".et_pb_map").length) { window.et_pb_map_init(jQuery(this)); }}); } }, 125); ',
                        $contentBlockerData['settings']
                    );

                    $content = preg_replace_callback(
                        '/(<div class="et_pb_map.+?(?=<\/div>)<\/div>){1}/i',
                        [$this, 'replaceGoogleMapsElement'],
                        $content
                    );
                }
            }
        }

        return $content;
    }

    /**
     * isBuilderModeActive function.
     *
     * @access public
     * @return void
     */
    public function isBuilderModeActive()
    {
        $hideCookieBox = false;

        if (function_exists('et_fb_enabled') && ! is_admin() && et_fb_enabled()) {
            $hideCookieBox = true;
        }

        if ($hideCookieBox) {
            add_filter("borlabsCookie/settings", function ($jsConfig) {
                $jsConfig['showCookieBox'] = false;

                return $jsConfig;
            });
        }
    }

    /**
     * loadGoogleMapsAPI function.
     *
     * @access public
     * @return void
     */
    public function loadGoogleMapsAPI()
    {
        add_action('wp_head', function () {
            $googleApiSettings = get_option('et_google_api_settings');
            echo '<script type=\'text/javascript\' src=\'https://maps.googleapis.com/maps/api/js?v=3&#038;key='
                . urlencode($googleApiSettings['api_key']) . '&#038;ver=' . ET_BUILDER_PRODUCT_VERSION . '\'></script>';
        });
    }

    /**
     * modifyDiviSettings function.
     *
     * @access public
     * @return void
     */
    public function modifyDiviSettings()
    {
        if (function_exists('et_fb_enabled') && ! is_admin() && et_fb_enabled()) {
            $this->loadGoogleMapsAPI();
        } else {
            // Only modify when Google Maps Content Blocker is active
            if (! empty(ContentBlocker::getInstance()->getContentBlockerData('googlemaps'))) {
                $googleApiSettings = get_option('et_google_api_settings');
                $googleApiSettings['enqueue_google_maps_script'] = false;
                update_option('et_google_api_settings', $googleApiSettings);
            } else {
                $googleApiSettings = get_option('et_google_api_settings');

                if (empty($googleApiSettings['enqueue_google_maps_script'])) {
                    $this->loadGoogleMapsAPI();
                }
            }
        }
    }

    /**
     * replaceGoogleMapsElement function.
     *
     * @access public
     *
     * @param  mixed  $mapElement
     *
     * @return void
     */
    public function replaceGoogleMapsElement($mapElement)
    {
        $mapElement[0] = ContentBlocker::getInstance()->handleContentBlocking($mapElement[0], '', 'googlemaps');

        return $mapElement[0];
    }
}
