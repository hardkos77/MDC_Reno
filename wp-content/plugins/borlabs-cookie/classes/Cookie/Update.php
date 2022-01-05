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

use stdClass;

class Update
{

    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    private $currentBlogId = '';

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
     * handlePluginAPI function.
     *
     * @access public
     *
     * @param  mixed  $result  Default is false
     * @param  string  $action  Type of information
     * @param  object  $args  Plugin API arguments
     *
     * @return void
     */
    public function handlePluginAPI($result, $action, $args)
    {
        if (! empty($action) && $action == 'plugin_information' && ! empty($args->slug)) {
            if ($args->slug == BORLABS_COOKIE_SLUG) {
                // Return alternative API URL
                $result = API::getInstance()->getPluginInformation();
            }
        }

        return $result;
    }

    /**
     * handleTransientUpdatePlugins function.
     *
     * @access public
     *
     * @param  mixed  $transient
     *
     * @return void
     */
    public function handleTransientUpdatePlugins($transient)
    {
        // If info is already available
        if (isset($transient->response[BORLABS_COOKIE_BASENAME])) {
            return $transient;
        }

        // Check for updates
        $updateInformation = API::getInstance()->getLatestVersion();

        if (! empty($updateInformation)) {
            if (version_compare(BORLABS_COOKIE_VERSION, $updateInformation->new_version, '<')) {
                // $transient can be null if third party plugins force a plugin refresh an kill the object
                if (! is_object($transient) && ! isset($transient->response)) {
                    $transient = new stdClass;
                    $transient->response = [];
                }
                $transient->response[BORLABS_COOKIE_BASENAME] = $updateInformation;
            }
        }

        return $transient;
    }

    /**
     * processUpgrade function.
     *
     * @access public
     * @return void
     */
    public function processUpgrade()
    {
        global $wpdb;

        $lastVersion = get_option('BorlabsCookieVersion', false);

        if (is_multisite()) {
            $allBlogs = $wpdb->get_results(
                "
                SELECT
                    `blog_id`
                FROM
                    `" . $wpdb->base_prefix . "blogs`
            "
            );
        }

        $versionUpgrades = Upgrade::getInstance()->getVersionUpgrades();

        if (! empty($lastVersion)) {
            foreach ($versionUpgrades as $upgradeFunction => $version) {
                if (version_compare($lastVersion, $version, '<')) {
                    if (method_exists(Upgrade::getInstance(), $upgradeFunction)) {
                        // Call upgrade function
                        call_user_func([Upgrade::getInstance(), $upgradeFunction]);

                        // Upgrade multisites
                        if (is_multisite() && ! empty($allBlogs)) {
                            $originalBlogId = get_current_blog_id();

                            foreach ($allBlogs as $blogData) {
                                if ($blogData->blog_id != 1) {
                                    switch_to_blog($blogData->blog_id);

                                    $this->currentBlogId = $blogData->blog_id;

                                    call_user_func([Upgrade::getInstance(), $upgradeFunction]);

                                    switch_to_blog($originalBlogId);
                                }
                            }

                            // Just in case we use this value at some later point
                            $this->currentBlogId = $originalBlogId;
                        }
                    }
                }
            }
        }
    }

    /**
     * upgradeComplete function.
     *
     * @access public
     *
     * @param  mixed  $upgraderObject
     * @param  mixed  $options
     *
     * @return void
     */
    public function upgradeComplete($upgraderObject, $options)
    {
        if ($options['action'] == 'update' && $options['type'] == 'plugin' && ! empty($options['plugins'])) {
            // Check if Borlabs Cookie was updated
            if (in_array(BORLABS_COOKIE_BASENAME, $options['plugins'])) {
                $this->processUpgrade();
            }
        }
    }
}
