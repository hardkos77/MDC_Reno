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

class Init
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
     * initBackend function.
     *
     * @access public
     * @return void
     */
    public function initBackend()
    {
        // Init all actions and filters which are relevant for the backend
        Backend\Backend::getInstance();
    }

    /**
     * initFrontend function.
     *
     * @access public
     * @return void
     */
    public function initFrontend()
    {
        // Init all actions and filters which are relevant for the frontend
        Frontend\Frontend::getInstance();
    }

    /**
     * initUpdateHooks function.
     *
     * @access public
     * @return void
     */
    public function initUpdateHooks()
    {
        /* Overwrite API URL when request infos about Borlabs Cookie */
        /* Changed priority to avoid a conflict when third-party-devs have a broken implementation for their plugin_information routine */
        add_action('plugins_api', [Update::getInstance(), 'handlePluginAPI'], 9001, 3);

        /* Register Hook for checking for updates */
        add_filter('pre_set_site_transient_update_plugins', [Update::getInstance(), 'handleTransientUpdatePlugins']);
    }

    /**
     * pluginActivated function.
     *
     * @access public
     * @return void
     */
    public function pluginActivated()
    {
        Install::getInstance()->installPlugin();
    }

    /**
     * pluginDeactivated function.
     *
     * @access public
     * @return void
     */
    public function pluginDeactivated()
    {
        wp_clear_scheduled_hook('borlabsCookieCron');
    }
}
