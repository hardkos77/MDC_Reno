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

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

if (version_compare(phpversion(), '5.6', '>=')) {

    $borlabsCookieWPLANG = get_option('WPLANG', 'en_US');

    if (empty($borlabsCookieWPLANG) || strlen($borlabsCookieWPLANG) <= 1) {
        $borlabsCookieWPLANG = 'en';
    }

    if (defined('BORLABS_COOKIE_IGNORE_ISO_639_1') === false) {
        define('BORLABS_COOKIE_DEFAULT_LANGUAGE', substr($borlabsCookieWPLANG, 0, 2));
    } else {
        define('BORLABS_COOKIE_DEFAULT_LANGUAGE', $borlabsCookieWPLANG);
    }

    include_once plugin_dir_path(__FILE__) . 'classes/Autoloader.php';

    $Autoloader = new \BorlabsCookie\Autoloader();
    $Autoloader->register();
    $Autoloader->addNamespace('BorlabsCookie', realpath(plugin_dir_path(__FILE__) . '/classes'));

    \BorlabsCookie\Cookie\Uninstall::getInstance()->uninstallPlugin();
}
?>
