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

use BorlabsCookie\Cookie\API;
use BorlabsCookie\Cookie\Backend\Maintenance;
use BorlabsCookie\Cookie\BackwardsCompatibility;
use BorlabsCookie\Cookie\Config;
use BorlabsCookie\Cookie\Multilanguage;

class Frontend
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', [$this, 'init']);
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
     * init function.
     *
     * @access public
     * @return void
     */
    public function init()
    {
        if (
            Config::getInstance()->get('cookieStatus') === true
            || (current_user_can('manage_borlabs_cookie')
                && Config::getInstance()->get('setupMode') === true)
        ) {
            /* Load textdomain */
            $this->loadTextdomain();

            // Handle API requests
            add_filter('query_vars', [API::getInstance(), 'addVars'], 0);
            add_filter('parse_request', [API::getInstance(), 'detectRequests'], 0);

            // Embed Custom Code
            add_action('wp', [Post::getInstance(), 'getCustomCode']);
            add_action('wp_footer', [Post::getInstance(), 'embedCustomCode']);

            // Add scripts and styles
            add_action('wp_enqueue_scripts', [Style::getInstance(), 'register']);
            add_action('wp_enqueue_scripts', [JavaScript::getInstance(), 'registerHead']);
            add_action('wp_head', [JavaScript::getInstance(), 'registerHeadFallback']);
            add_action('wp_footer', [JavaScript::getInstance(), 'registerFooter']);

            // Detect and modify scripts
            add_action(
                'template_redirect',
                [Buffer::getInstance(), 'handleBuffering'],
                19021987
            ); // Will be used by ScriptBlocker->handleJavaScriptTagBlocking() && ScriptBlocker->detectJavaScriptsTags()
            add_filter('script_loader_tag', [ScriptBlocker::getInstance(), 'detectHandles'], 990, 3);
            add_filter('script_loader_tag', [ScriptBlocker::getInstance(), 'blockHandles'], 999, 3);
            add_action('wp_footer', [ScriptBlocker::getInstance(), 'detectJavaScriptsTags'], 998);
            add_action('wp_footer', [ScriptBlocker::getInstance(), 'saveDetectedJavaScripts'], 999);
            add_action(
                'wp_footer',
                [ScriptBlocker::getInstance(), 'handleJavaScriptTagBlocking'],
                19021987
            ); // Late but not latest

            // Embed Cookie Box
            add_action('wp_footer', [CookieBox::getInstance(), 'insertCookieBox']);

            // Block cookies
            add_action('wp', [CookieBlocker::getInstance(), 'handleBlocking']);

            // Register shortcodes
            add_shortcode('borlabs-cookie', [Shortcode::getInstance(), 'handleShortcode']);

            add_filter('the_content', [ContentBlocker::getInstance(), 'detectIframes'], 100, 1);
            add_filter('embed_oembed_html', [ContentBlocker::getInstance(), 'handleOembed'], 100, 4);
            add_filter('widget_custom_html_content', [ContentBlocker::getInstance(), 'detectIframes'], 100, 1);
            add_filter('widget_text_content', [ContentBlocker::getInstance(), 'detectIframes'], 100, 1);
            add_filter('widget_block_content', [ContentBlocker::getInstance(), 'detectIframes'], 100, 1);

            // Register Cookie Box for login page
            if (Config::getInstance()->get('showCookieBoxOnLoginPage') === true) {
                add_action('login_enqueue_scripts', [Style::getInstance(), 'register']);
                add_action('login_enqueue_scripts', [JavaScript::getInstance(), 'registerHead']);
                add_action('login_head', [JavaScript::getInstance(), 'registerHeadFallback']);
                add_action('login_footer', [JavaScript::getInstance(), 'registerFooter']);
                add_action('login_footer', [CookieBox::getInstance(), 'insertCookieBox']);
            }

            // Cron
            add_action('borlabsCookieCron', [Maintenance::getInstance(), 'cleanUp']);

            if (! wp_next_scheduled('borlabsCookieCron')) {
                wp_schedule_event(time(), 'daily', 'borlabsCookieCron');
            }

            // THIRD PARTY
            // ACF
            if (class_exists('ACF')) {
                ThirdParty\Plugins\ACF::getInstance()->register();
            }

            // Avada
            if (defined('AVADA_VERSION')) {
                add_action(
                    'fusion_builder_enqueue_live_scripts',
                    [ThirdParty\Themes\Avada::getInstance(), 'adminHeadCSS'],
                    100
                );
                add_action(
                    'wp_footer',
                    [ThirdParty\Themes\Avada::getInstance(), 'disableBuffer'],
                    1
                );
            }

            // Bricks
            if (defined('BRICKS_VERSION')) {
                add_action(
                    'bricks/frontend/render_sections',
                    [ThirdParty\Themes\Bricks::getInstance(), 'detectIframes'],
                    100
                );
            }

            // Divi
            if (function_exists('et_divi_builder_init_plugin') || function_exists('et_setup_theme')) {
                add_action('wp', [ThirdParty\Themes\Divi::getInstance(), 'modifyDiviSettings']);
                add_action('wp', [ThirdParty\Themes\Divi::getInstance(), 'isBuilderModeActive']);
                add_filter('the_content', [ThirdParty\Themes\Divi::getInstance(), 'detectGoogleMaps'], 100, 1);
                add_filter(
                    'et_builder_render_layout',
                    [ThirdParty\Themes\Divi::getInstance(), 'detectGoogleMaps'],
                    100,
                    1
                );
                add_filter('et_builder_render_layout', [ContentBlocker::getInstance(), 'detectIframes'], 100, 1);
            }

            // Elementor
            if (defined('ELEMENTOR_VERSION')) {
                if (version_compare(ELEMENTOR_VERSION, '3.0', '>=')) {
                    add_action(
                        'elementor/element/after_add_attributes',
                        [ThirdParty\Themes\Elementor::getInstance(), 'detectYouTubeVideoWidget'],
                        100,
                        1
                    );
                }
                add_action(
                    'elementor/widget/render_content',
                    [ThirdParty\Themes\Elementor::getInstance(), 'detectFacebook'],
                    100,
                    2
                );
                add_action(
                    'elementor/widget/render_content',
                    [ThirdParty\Themes\Elementor::getInstance(), 'detectIframes'],
                    100,
                    2
                );
            }

            // Enfold
            if (function_exists('avia_register_frontend_scripts')) {
                add_action(
                    'avf_sc_video_output',
                    [ThirdParty\Themes\Enfold::getInstance(), 'modifyVideoOutput'],
                    100,
                    6
                );
            }

            // Ezoic
            add_filter('script_loader_tag', [ThirdParty\Providers\Ezoic::getInstance(), 'addDataAttribute'], 100, 3);

            // Gravity Forms - Iframe Add-on
            if (function_exists('gfiframe_autoloader')) {
                ThirdParty\Plugins\GravityFormsIframe::getInstance()->register();
            }

            // Oxygen Builder
            if (function_exists('oxygen_activate_plugin')) {
                ThirdParty\Plugins\Oxygen::getInstance()->register();
            }

            // The Events Calendar
            if (defined('TRIBE_EVENTS_FILE')) {
                ThirdParty\Plugins\TheEventsCalendar::getInstance()->register();
            }

            // Backwards Compatibility
            add_shortcode('borlabs_cookie_blocked_content',
                [BackwardsCompatibility::getInstance(), 'shortcodeBlockedContent']);
        }
    }

    /**
     * loadTextdomain function.
     *
     * @access public
     * @return void
     */
    public function loadTextdomain()
    {
        load_plugin_textdomain('borlabs-cookie', false, BORLABS_COOKIE_SLUG . '/languages/');

        // Load correct DE language file if any DE language was selected
        if (
            in_array(Multilanguage::getInstance()->getCurrentLanguageCode(),
                ['de', 'de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal'])
        ) {
            // Load german language pack
            load_textdomain('borlabs-cookie', BORLABS_COOKIE_PLUGIN_PATH . 'languages/borlabs-cookie-de_DE.mo');
        }
    }
}
