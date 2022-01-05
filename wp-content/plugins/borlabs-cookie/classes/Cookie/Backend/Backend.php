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
use BorlabsCookie\Cookie\Frontend\ConsentLog;
use BorlabsCookie\Cookie\Frontend\Shortcode;
use BorlabsCookie\Cookie\Frontend\ThirdParty\Plugins\PixelYourSite;
use BorlabsCookie\Cookie\Multilanguage;
use BorlabsCookie\Cookie\Upgrade;

class Backend
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

    public function __construct()
    {
        /* Load textdomain */
        add_action('init', [$this, 'loadTextdomain']);

        /* Add menu */
        add_action('admin_menu', [$this, 'addMenu']);

        /* Load JavaScript & CSS */
        add_action('admin_enqueue_scripts', [$this, 'registerAdminRessources']);

        /* Add action links to plugin page */
        add_filter('plugin_action_links_' . BORLABS_COOKIE_BASENAME, [$this, 'addActionLinks']);

        /* Extend update plugin message */
        add_action('in_plugin_update_message-' . BORLABS_COOKIE_BASENAME, [$this, 'extendPluginUpdateMessage'], 10, 2);

        /* Meta Box */
        add_action('wp_loaded', [MetaBox::getInstance(), 'register']);

        /* Register handler for AJAX requests */
        add_action('wp_ajax_borlabs_cookie_handler', [$this, 'handleAjaxRequest']);
        add_action('wp_ajax_nopriv_borlabs_cookie_handler', [$this, 'handleAjaxRequest']);

        /* Register shortcodes */
        if (wp_doing_ajax() === true) {
            add_shortcode('borlabs-cookie', [Shortcode::getInstance(), 'handleShortcode']);
        }

        /* System Check */
        add_action('current_screen', [$this, 'handleSystemCheck']);

        // THIRD PARTY
        // PixelYourSite
        add_action('wp_loaded', [PixelYourSite::getInstance(), 'register']);

        $this->templatePath = realpath(dirname(__FILE__) . '/../../../templates');
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
     * addActionLinks function.
     *
     * @access public
     *
     * @param  mixed  $links
     *
     * @return void
     */
    public function addActionLinks($links)
    {
        if (is_array($links)) {
            array_unshift(
                $links,
                '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=borlabs-cookie')) . '">' . _x(
                    'Dashboard',
                    'Backend / WordPress Core / Plugins / Text',
                    'borlabs-cookie'
                ) . '</a>',
                '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=borlabs-cookie-settings')) . '">' . _x(
                    'Settings',
                    'Backend / WordPress Core / Plugins / Text',
                    'borlabs-cookie'
                ) . '</a>',
                '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=borlabs-cookie-license')) . '">' . _x(
                    'License',
                    'Backend / WordPress Core / Plugins / Text',
                    'borlabs-cookie'
                ) . '</a>',
                '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=borlabs-cookie-help')) . '">' . _x(
                    'Help',
                    'Backend / WordPress Core / Plugins / Text',
                    'borlabs-cookie'
                ) . '</a>'
            );
        }

        return $links;
    }

    /**
     * addMenu function.
     *
     * @access public
     * @return void
     */
    public function addMenu()
    {
        /* Main menu */
        add_menu_page(
            _x('Borlabs Cookie', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Borlabs Cookie', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie', /* lowest administrator level */ 'borlabs-cookie',
            [View::getInstance(), 'Dashboard'],
            Icons::getInstance()->getAdminSVGIcon(),
            null /* menu position */
        );

        /* Dashboard */
        add_submenu_page('borlabs-cookie',
            _x('Dashboard', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Dashboard', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie',
            [View::getInstance(), 'Dashboard']);

        /* Settings */
        add_submenu_page('borlabs-cookie',
            _x('Settings', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Settings', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-settings',
            [View::getInstance(), 'Settings']);

        /* Cookie Box */
        add_submenu_page('borlabs-cookie',
            _x('Cookie Box', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Cookie Box', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-cookie-box',
            [View::getInstance(), 'CookieBox']);

        /* Cookie Groups */
        add_submenu_page('borlabs-cookie',
            _x('Cookie Groups', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Cookie Groups', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-cookie-groups',
            [View::getInstance(), 'CookieGroups']);

        /* Cookies */
        add_submenu_page('borlabs-cookie',
            _x('Cookies', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Cookies', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-cookies',
            [View::getInstance(), 'Cookies']);

        /* Content Blocker */
        add_submenu_page('borlabs-cookie',
            _x('Content Blocker', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Content Blocker', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-content-blocker',
            [View::getInstance(), 'ContentBlocker']);

        /* Script Blocker */
        add_submenu_page('borlabs-cookie',
            _x('Script Blocker', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Script Blocker', 'Backend / Global / Site Title', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-script-blocker',
            [View::getInstance(), 'ScriptBlocker']);

        /* Import & Export */
        add_submenu_page('borlabs-cookie',
            _x('Import & Export', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Import & Export', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-import-export',
            [View::getInstance(), 'ImportExport']);

        /* License */
        add_submenu_page('borlabs-cookie',
            _x('License', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('License', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-license',
            [View::getInstance(), 'License']);

        /* Help & Support */
        add_submenu_page('borlabs-cookie',
            _x('Help & Support', 'Backend / Global / Site Title', 'borlabs-cookie'),
            _x('Help & Support', 'Backend / Global / Menu Entry', 'borlabs-cookie'),
            'manage_borlabs_cookie',
            'borlabs-cookie-help',
            [View::getInstance(), 'Help']);
    }

    /**
     * extendPluginUpdateMessage function.
     *
     * @access public
     *
     * @param  mixed  $pluginData
     * @param  mixed  $response
     *
     * @return void
     */
    public function extendPluginUpdateMessage($pluginData, $response)
    {
        // Check license
        $licenseData = License::getInstance()->getLicenseData();

        if (empty($licenseData)) {
            echo "<br>";
            echo License::getInstance()->getLicenseMessageEnterKey();
        } elseif (! empty($licenseData->validUntil) && strtotime($licenseData->validUntil) < strtotime(date('Y-m-d'))) {
            echo "<br>";
            echo License::getInstance()->getLicenseMessageKeyExpired();
        }
    }

    /**
     * handleAjaxRequest function.
     *
     * @access public
     * @return void
     */
    public function handleAjaxRequest()
    {
        if (! empty($_POST['type'])) {
            $requestType = $_POST['type'];

            /* Frontend request */
            if ($requestType == 'log') {
                if (! empty($_POST['cookieData']) && ! empty($_POST['language'])) {
                    echo json_encode([
                        'success' => ConsentLog::getInstance()->add($_POST['cookieData'], $_POST['language']),
                    ]);
                }
            } elseif ($requestType == 'consent_history') {
                if (! empty($_POST['uid'])) {
                    $language = Multilanguage::getInstance()->getCurrentLanguageCode();

                    if (! empty($_POST['language'])) {
                        $language = $_POST['language'];
                    }

                    echo json_encode(ConsentLog::getInstance()->getConsentHistory($_POST['uid'], $language));
                }
            } elseif ($requestType == 'get_page') {
                /* Backend request */
                if (check_ajax_referer('borlabs-cookie-cookie-box', false, false)) {
                    $permalink = '';

                    if (! empty($_POST['pageId'])) {
                        $permalink = get_permalink(intval($_POST['pageId']));
                    }

                    echo json_encode(['permalink' => $permalink]);
                }
            } elseif ($requestType == 'clean_up') {
                /* Backend request */
                if (check_ajax_referer('borlabs-cookie', false, false)) {
                    Maintenance::getInstance()->cleanUp(true);

                    $totalConsentLogs = number_format_i18n(SystemCheck::getInstance()->getTotalConsentLogs());
                    $consentLogTableSize = number_format_i18n(SystemCheck::getInstance()->getConsentLogTableSize(), 2);

                    echo json_encode(['total' => $totalConsentLogs, 'size' => $consentLogTableSize]);
                }
            } elseif ($requestType == 'scan_javascripts') {
                /* Backend request */
                if (check_ajax_referer('borlabs-cookie-script-blocker', false, false)) {
                    if (
                        ! empty($_POST['scanURL']) && ! empty($_POST['getScanResults'])
                        && $_POST['getScanResults'] === 'false'
                    ) {
                        // Reset scanned JavaScript result
                        update_option('BorlabsCookieDetectedJavaScripts', [], 'no');

                        $statusScanRequest = ScriptBlocker::getInstance()->handleScanRequest(
                            $_POST['scanURL'],
                            stripslashes($_POST['searchPhrases'])
                        );

                        // Fallback URL: user has to visit the website manually
                        $scanURLManually = '';
                        $urlQuery = [];
                        $scanURLInfo = parse_url($_POST['scanURL']);

                        if (! empty($scanURLInfo['query'])) {
                            parse_str($scanURLInfo['query'], $urlQuery);
                        }

                        $urlQuery['__borlabsCookieScanJavaScripts'] = true;

                        $scanURLManually = $scanURLInfo['scheme'] . '://' . $scanURLInfo['host'] . $scanURLInfo['path']
                            . '?' . http_build_query($urlQuery);

                        echo json_encode(['success' => $statusScanRequest, 'scanURLManually' => $scanURLManually]);
                    } elseif (! empty($_POST['getScanResults'])) {
                        // Fallback - Check if user has visited the website and JavaScripts were found
                        $detectedJavaScripts = get_option('BorlabsCookieDetectedJavaScripts', []);

                        echo json_encode(
                            ['success' => ! empty(count($detectedJavaScripts, COUNT_RECURSIVE)) ? true : false]);
                    }
                }
            }
        }

        wp_die();
    }

    /**
     * handleSystemCheck function.
     *
     * @access public
     * @return void
     */
    public function handleSystemCheck()
    {
        $currentScreenData = get_current_screen();

        if (strpos($currentScreenData->id, 'borlabs-cookie') !== false) {
            /* Check if license is expired */
            if ($currentScreenData->id !== "borlabs-cookie_page_borlabs-cookie-license") {
                License::getInstance()->handleLicenseExpiredMessage();
            }

            /* Check if cache should be cleared after upgrade */
            $clearCache = get_option('BorlabsCookieClearCache', false);

            if ($clearCache == true) {
                Upgrade::getInstance()->clearCache();
            }

            /* System Check */
            $statusSystemCheck = [];

            $statusSystemCheck[] = SystemCheck::getInstance()->checkCacheFolders();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkSSLSettings();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkLanguageSettings();

            $statusSystemCheck[] = SystemCheck::getInstance()->checkTableContentBlocker();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkTableCookieConsentLog();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkTableCookieGroups();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkTableCookies();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkTableScriptBlocker();

            $statusSystemCheck[] = SystemCheck::getInstance()->checkDefaultContentBlocker();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkDefaultCookieGroups();
            $statusSystemCheck[] = SystemCheck::getInstance()->checkDefaultCookies();

            if (! empty($statusSystemCheck)) {
                foreach ($statusSystemCheck as $statusData) {
                    if ($statusData['success'] === false) {
                        Messages::getInstance()->add($statusData['message'], 'error');
                    }
                }
            }

            // Check if Borlabs Cookie is active but only if plugin is unlocked
            if (License::getInstance()->isPluginUnlocked()) {
                if (Config::getInstance()->get('cookieStatus') === false && empty($_POST['cookieStatus'])) {
                    Messages::getInstance()->add(
                        _x(
                            'Borlabs Cookie is not active. If you want to use Borlabs Cookies features on your website, please activate it under <strong>Settings &gt; Borlabs Cookie Status</strong>.',
                            'Backend / Global / Alert Message',
                            'borlabs-cookie'
                        ),
                        'warning'
                    );
                }
            }
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

        // Weglot special
        if (Multilanguage::getInstance()->isLanguagePluginWeglotActive()) {
            $langFileMap = [
                'de' => 'borlabs-cookie-de_DE.mo',
                'es' => 'borlabs-cookie-es_ES.mo',
                'fr' => 'borlabs-cookie-fr_FR.mo',
                'it' => 'borlabs-cookie-it_IT.mo',
                'nl' => 'borlabs-cookie-nl_NL.mo',
                'pl' => 'borlabs-cookie-pl_PL.mo',
            ];

            if (isset($langFileMap[Multilanguage::getInstance()->getCurrentLanguageCode()])) {
                load_textdomain(
                    'borlabs-cookie',
                    BORLABS_COOKIE_PLUGIN_PATH . 'languages/' . $langFileMap[Multilanguage::getInstance()
                        ->getCurrentLanguageCode()]
                );

                return;
            }
        }

        // Load correct DE language file if any DE language was selected
        if (
            in_array(Multilanguage::getInstance()->getCurrentLanguageCode(),
                ['de', 'de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal'])
        ) {
            // Load german language pack
            load_textdomain('borlabs-cookie', BORLABS_COOKIE_PLUGIN_PATH . 'languages/borlabs-cookie-de_DE.mo');
        }
    }

    /**
     * registerAdminRessources function.
     *
     * @access public
     * @return void
     */
    public function registerAdminRessources()
    {
        $currentScreenData = get_current_screen();

        if (strpos($currentScreenData->id, 'borlabs-cookie') !== false) {
            wp_enqueue_style(
                'borlabs-cookie-wordpress-admin-style',
                plugins_url('assets/css/borlabs-cookie-wordpress-admin-style.css', realpath(__DIR__ . '/../../')),
                [],
                BORLABS_COOKIE_VERSION
            );

            wp_enqueue_style(
                'borlabs-cookie-fontawesome',
                plugins_url(
                    'node_modules/@fortawesome/fontawesome-free/css/fontawesome.min.css',
                    realpath(__DIR__ . '/../../')
                ),
                [],
                '5.5.0'
            );

            wp_enqueue_style(
                'borlabs-cookie-fontawesome-solid',
                plugins_url(
                    'node_modules/@fortawesome/fontawesome-free/css/solid.min.css',
                    realpath(__DIR__ . '/../../')
                ),
                [],
                '5.5.0'
            );

            wp_enqueue_style(
                'borlabs-cookie-animate',
                plugins_url('node_modules/animate.css/animate.min.css', realpath(__DIR__ . '/../../')),
                [],
                '3.7.0'
            );

            wp_enqueue_script(
                'borlabs-cookie-bootstrap',
                plugins_url('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', realpath(__DIR__ . '/../../')),
                ['jquery'],
                '4.1.3',
                true
            );

            wp_add_inline_script(
                'borlabs-cookie-bootstrap',
                'jQuery.fn.borlabsBootstrapTooltip = jQuery.fn.tooltip.noConflict();',
                'after'
            );

            if ($currentScreenData->base === 'toplevel_page_borlabs-cookie') {
                wp_enqueue_script(
                    'borlabs-cookie-chartjs',
                    plugins_url('node_modules/chart.js/dist/Chart.min.js', realpath(__DIR__ . '/../../')),
                    ['jquery'],
                    '2.8.0',
                    true
                );
            }

            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script(
                'borlabs-cookie-wordpress-admin-script',
                plugins_url(
                    'assets/javascript/borlabs-cookie-wordpress-admin-script.min.js',
                    realpath(__DIR__ . '/../../')
                ),
                ['wp-color-picker', 'borlabs-cookie-bootstrap'],
                BORLABS_COOKIE_VERSION,
                true
            );

            // Media Library
            wp_enqueue_media();

            wp_localize_script('borlabs-cookie-wordpress-admin-script', 'borlabsCookieAdmin', [
                'ajax_nonce' => wp_create_nonce('borlabs-cookie-cookie-box'),
                'ajax_nonce_scan_javascripts' => wp_create_nonce('borlabs-cookie-script-blocker'),
                'ajax_nonce_clean_up' => wp_create_nonce('borlabs-cookie'),
            ]);

            // CodeMirror - WordPress 4.9.x
            if (function_exists('wp_enqueue_code_editor')) {
                // Enqueue code editor and settings for manipulating HTML.
                $settingsHTML = wp_enqueue_code_editor(
                    ['type' => 'text/html', 'htmlhint' => ['space-tab-mixed-disabled' => false]]);

                if ($settingsHTML !== false) {
                    wp_add_inline_script(
                        'code-editor',
                        sprintf(
                            'jQuery( function() { if (jQuery("#BorlabsCookie [data-borlabs-html-editor]").length) {  jQuery("#BorlabsCookie [data-borlabs-html-editor]").each(function () { wp.codeEditor.initialize(this.id, %s); }); } } );',
                            wp_json_encode($settingsHTML)
                        )
                    );
                }

                // Enqueue code editor and settings for manipulating JavaScript.
                $settingsJS = wp_enqueue_code_editor(['type' => 'text/javascript']);

                if ($settingsJS !== false) {
                    wp_add_inline_script(
                        'code-editor',
                        sprintf(
                            'jQuery( function() { if (jQuery("#BorlabsCookie [data-borlabs-js-editor]").length) { jQuery("#BorlabsCookie [data-borlabs-js-editor]").each(function () { wp.codeEditor.initialize(this.id, %s); }); } } );',
                            wp_json_encode($settingsJS)
                        )
                    );
                }

                // Enqueue code editor and settings for manipulating CSS.
                $settingsCSS = wp_enqueue_code_editor(['type' => 'text/css']);

                if ($settingsCSS !== false) {
                    wp_add_inline_script(
                        'code-editor',
                        sprintf(
                            'jQuery( function() { if (jQuery("#BorlabsCookie [data-borlabs-css-editor]").length) { jQuery("#BorlabsCookie [data-borlabs-css-editor]").each(function () { wp.codeEditor.initialize(this.id, %s); }); } } );',
                            wp_json_encode($settingsCSS)
                        )
                    );
                }
            }
        } else {
            if (
                ! empty($currentScreenData->post_type)
                && ! empty(
                Config::getInstance()->get(
                    'metaBox'
                )[$currentScreenData->post_type]
                )
            ) {
                wp_enqueue_style(
                    'borlabs-cookie-wordpress-admin-style',
                    plugins_url('assets/css/borlabs-cookie-wordpress-admin-style.css', realpath(__DIR__ . '/../../')),
                    [],
                    BORLABS_COOKIE_VERSION
                );
            }
        }
    }
}
