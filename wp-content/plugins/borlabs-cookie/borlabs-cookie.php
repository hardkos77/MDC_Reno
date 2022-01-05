<?php
/*
Plugin Name: Borlabs Cookie - Cookie Opt-in
Plugin URI: https://borlabs.io/
Description: Borlabs Cookie is an easy to use cookie opt-in and content block solution for WordPress. Create detailed descriptions for cookies and sort them in customizable 'Cookie Groups'. Create specific 'Content Blockers' and block everything from YouTube media to Facebook posts. Let your visitors choose which cookies they want to allow and what content they want to see. Borlabs Cookie helps you to make your website ready for GDPR & ePrivacy regulations.
Author: Benjamin A. Bornschein, Borlabs
Author URI: https://borlabs.io
Version: 2.2.39
Text Domain: borlabs-cookie
Domain Path: /languages
*/

$borlabsCookieWPLANG = get_option('WPLANG', 'en_US');

if (empty($borlabsCookieWPLANG) || strlen($borlabsCookieWPLANG) <= 1) {
    $borlabsCookieWPLANG = 'en';
}

define('BORLABS_COOKIE_VERSION', '2.2.39');
define('BORLABS_COOKIE_BASENAME', plugin_basename(__FILE__));
define('BORLABS_COOKIE_SLUG', basename(BORLABS_COOKIE_BASENAME, '.php'));
define('BORLABS_COOKIE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BORLABS_COOKIE_PLUGIN_URL', plugin_dir_url(__FILE__));

if (defined('BORLABS_COOKIE_IGNORE_ISO_639_1') === false) {
    define('BORLABS_COOKIE_DEFAULT_LANGUAGE', substr($borlabsCookieWPLANG, 0, 2));
} else {
    define('BORLABS_COOKIE_DEFAULT_LANGUAGE', $borlabsCookieWPLANG);
}

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

if (version_compare(phpversion(), '5.6', '>=')) {
    include_once plugin_dir_path(__FILE__) . 'classes/Autoloader.php';

    $Autoloader = new \BorlabsCookie\Autoloader();
    $Autoloader->register();
    $Autoloader->addNamespace('BorlabsCookie', realpath(plugin_dir_path(__FILE__) . '/classes'));

    register_activation_hook(__FILE__, array(\BorlabsCookie\Cookie\Init::getInstance(), 'pluginActivated'));
    register_deactivation_hook(__FILE__, array(\BorlabsCookie\Cookie\Init::getInstance(), 'pluginDeactivated'));

    /* Init plugin */
    if (is_admin()) {
        /* Backend */
        \BorlabsCookie\Cookie\Init::getInstance()->initBackend();
    } else {
        /* Frontend */
        \BorlabsCookie\Cookie\Init::getInstance()->initFrontend();
    }

    /* Update*/
    if ((defined('WP_CLI') && WP_CLI === true) || is_admin()) {
        \BorlabsCookie\Cookie\Init::getInstance()->initUpdateHooks();
    }

    /* Call after upgrade process is complete */
    add_action(
        'upgrader_process_complete',
        array(\BorlabsCookie\Cookie\Update::getInstance(), 'upgradeComplete'),
        10,
        2
    );

    /* Fallback if the upgrade of Borlabs Cookie was not initiated via the upgrade process but replaced manually or even worse: via Composer */
    add_action('plugins_loaded', function () {
        $lastVersion = get_option('BorlabsCookieVersion', false);

        /* If no last version exists, an upgrade is not needed */
        if ($lastVersion === false) {
            return;
        }

        if (defined('BORLABS_COOKIE_VERSION') && version_compare(BORLABS_COOKIE_VERSION, $lastVersion, '>')) {
            \BorlabsCookie\Cookie\Update::getInstance()->processUpgrade();
        }
    });

    /* Third Party Developer Helper Class Shortcut Function - fun fact: in german this would be a single noun! */
    if (! function_exists('BorlabsCookieHelper')) {
        function BorlabsCookieHelper()
        {
            return \BorlabsCookie\Cookie\ThirdPartyHelper::getInstance();
        }
    }
} else {
    //! Fallback for very old php version
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error">
            <p><?php
                _ex(
                    'Your PHP version is <a href="http://php.net/supported-versions.php" rel="nofollow noopener noreferrer" target="_blank">outdated</a> and not supported by Borlabs Cookie. Please disable Borlabs Cookie, upgrade to PHP 5.6 or higher, and enable Borlabs Cookie again. It is necessary to follow these steps in the exact order described.',
                    'Backend / Global / Alert Message',
                    'borlabs-cookie'
                ); ?></p>
        </div>
        <?php
    });
}
?>
