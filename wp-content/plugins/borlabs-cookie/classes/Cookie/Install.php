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

use BorlabsCookie\Cookie\Backend\ContentBlocker;
use WP_Roles;

class Install
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
     * addUserCapabilities function.
     *
     * @access public
     */
    public function addUserCapabilities()
    {
        global $wp_roles;

        if (! isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $capabilities = $this->getCapabilities();

        foreach ($capabilities as $cap) {
            $wp_roles->add_cap('administrator', $cap);
        }
    }

    /**
     * checkIfColumnExists function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $columnName
     *
     */
    public function checkIfColumnExists($tableName, $columnName)
    {
        global $wpdb;

        $dbName = $wpdb->dbname;

        // HyperDB workaround
        if (empty($dbName) && defined('DB_NAME')) {
            $dbName = DB_NAME;
        }

        $tableResult = $wpdb->get_results(
            "
            SELECT
                `COLUMN_NAME`
            FROM
                `information_schema`.`COLUMNS`
            WHERE
                `TABLE_SCHEMA` = '" . esc_sql($dbName) . "'
                AND
                `TABLE_NAME` = '" . esc_sql($tableName) . "'
                AND
                `COLUMN_NAME` = '" . esc_sql($columnName) . "'
        "
        );

        if (! empty($tableResult[0]->COLUMN_NAME)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checkIfIndexExists function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $indexName
     */
    public function checkIfIndexExists($tableName, $indexName)
    {
        global $wpdb;

        $tableResult = $wpdb->get_results(
            "
            SHOW
                INDEXES
            FROM
                `" . $tableName . "`
            WHERE
                `Key_name` = '" . esc_sql($indexName) . "'
        "
        );

        if (! empty($tableResult[0]->Key_name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checkIfTableExists function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     */
    public function checkIfTableExists($tableName)
    {
        global $wpdb;

        $dbName = $wpdb->dbname;

        // HyperDB workaround
        if (empty($dbName) && defined('DB_NAME')) {
            $dbName = DB_NAME;
        }

        $tableResult = $wpdb->get_results(
            "
            SELECT
                `TABLE_NAME`
            FROM
                `information_schema`.`TABLES`
            WHERE
                `TABLE_SCHEMA` = '" . esc_sql($dbName) . "'
                AND
                `TABLE_NAME` = '" . esc_sql($tableName) . "'
        "
        );

        if (! empty($tableResult[0]->TABLE_NAME)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checkTypeOfColumn function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $columnName
     * @param  mixed  $expectedType
     */
    public function checkTypeOfColumn($tableName, $columnName, $expectedType)
    {
        global $wpdb;

        $dbName = $wpdb->dbname;

        // HyperDB workaround
        if (empty($dbName) && defined('DB_NAME')) {
            $dbName = DB_NAME;
        }

        $tableResult = $wpdb->get_results(
            "
            SELECT
                `DATA_TYPE`
            FROM
                `information_schema`.`COLUMNS`
            WHERE
                `TABLE_SCHEMA` = '" . esc_sql($dbName) . "'
                AND
                `TABLE_NAME` = '" . esc_sql($tableName) . "'
                AND
                `COLUMN_NAME` = '" . esc_sql($columnName) . "'
        "
        );

        if (
            ! empty($tableResult[0]->DATA_TYPE)
            && strtolower($tableResult[0]->DATA_TYPE) == strtolower(
                $expectedType
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getCapabilities function.
     *
     * @access public
     */
    public function getCapabilities()
    {
        $capabilities = ['manage_borlabs_cookie'];

        return $capabilities;
    }

    /**
     * getCreateTableStatementContentBlocker function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $charsetCollate
     */
    public function getCreateTableStatementContentBlocker($tableName, $charsetCollate)
    {
        return "CREATE TABLE IF NOT EXISTS " . $tableName . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `content_blocker_id` varchar(35) NOT NULL DEFAULT '',
            `language` varchar(16) NOT NULL DEFAULT '',
            `name` varchar(100) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `privacy_policy_url` varchar(255) NOT NULL DEFAULT '',
            `hosts` TEXT NOT NULL,
            `preview_html` TEXT NOT NULL,
            `preview_css` TEXT NOT NULL,
            `global_js` TEXT NOT NULL,
            `init_js` TEXT NOT NULL,
            `settings` TEXT NOT NULL,
            `status` int(1) unsigned NOT NULL DEFAULT '0',
            `undeletable` int(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY (`content_blocker_id`, `language`)
        ) " . $charsetCollate . ";";
    }

    /**
     * getCreateTableStatementCookieConsentLog function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $charsetCollate
     */
    public function getCreateTableStatementCookieConsentLog($tableName, $charsetCollate)
    {
        return "CREATE TABLE IF NOT EXISTS " . $tableName . " (
            `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `uid` varchar(35) NOT NULL DEFAULT '',
            `cookie_version` int(11) unsigned DEFAULT NULL,
            `consents` text,
            `is_latest` int(11) unsigned DEFAULT '0',
            `stamp` datetime DEFAULT NULL,
            PRIMARY KEY (`log_id`),
            KEY `uid` (`uid`, `is_latest`)
        ) " . $charsetCollate . ";";
    }

    /**
     * getCreateTableStatementCookieGroups function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $charsetCollate
     */
    public function getCreateTableStatementCookieGroups($tableName, $charsetCollate)
    {
        return "CREATE TABLE IF NOT EXISTS " . $tableName . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `group_id` varchar(35) NOT NULL,
            `language` varchar(16) NOT NULL DEFAULT '',
            `name` varchar(100) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `pre_selected` int(1) NOT NULL DEFAULT '0',
            `position` int(11) unsigned NOT NULL DEFAULT '0',
            `status` int(1) unsigned NOT NULL DEFAULT '0',
            `undeletable` int(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `group_id` (`group_id`,`language`)
        ) " . $charsetCollate . ";";
    }

    /**
     * getCreateTableStatementCookies function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $charsetCollate
     */
    public function getCreateTableStatementCookies($tableName, $charsetCollate)
    {
        return "CREATE TABLE IF NOT EXISTS " . $tableName . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `cookie_id` varchar(35) NOT NULL DEFAULT '',
            `language` varchar(16) NOT NULL,
            `cookie_group_id` int(11) unsigned NOT NULL DEFAULT '1',
            `service` varchar(35) NOT NULL,
            `name` varchar(100) NOT NULL DEFAULT '',
            `provider` varchar(100) NOT NULL DEFAULT '',
            `purpose` text NOT NULL COMMENT 'Track everything',
            `privacy_policy_url` varchar(255) NOT NULL,
            `hosts` text NOT NULL,
            `cookie_name` TEXT NOT NULL,
            `cookie_expiry` TEXT NOT NULL,
            `opt_in_js` text NOT NULL,
            `opt_out_js` text NOT NULL,
            `fallback_js` text NOT NULL,
            `settings` text NOT NULL,
            `position` int(11) unsigned NOT NULL DEFAULT '0',
            `status` int(1) unsigned NOT NULL DEFAULT '0',
            `undeletable` int(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `cookie_id` (`cookie_id`,`language`),
            KEY `cookie_group_id` (`cookie_group_id`)
        ) " . $charsetCollate . ";";
    }

    /**
     * getCreateTableStatementScriptBlocker function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $charsetCollate
     *
     */
    public function getCreateTableStatementScriptBlocker($tableName, $charsetCollate)
    {
        return "CREATE TABLE IF NOT EXISTS " . $tableName . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `script_blocker_id` varchar(35) NOT NULL DEFAULT '',
            `name` varchar(100) NOT NULL DEFAULT '',
            `handles` TEXT NOT NULL,
            `js_block_phrases` TEXT NOT NULL,
            `status` int(1) unsigned NOT NULL DEFAULT '0',
            `undeletable` int(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `script_blocker_id` (`script_blocker_id`)
        ) " . $charsetCollate . ";";
    }

    /**
     * getDefaultEntriesCookieGroups function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $language
     *
     */
    public function getDefaultEntriesCookieGroups($tableName, $language)
    {
        return "INSERT INTO `" . $tableName . "`
        (
            `group_id`,
            `language`,
            `name`,
            `description`,
            `pre_selected`,
            `position`,
            `status`,
            `undeletable`
        )
        VALUES
        (
            'essential',
            '" . esc_sql($language) . "',
            '" . esc_sql(_x('Essential', 'Frontend / Cookie Groups / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(
                _x(
                    'Essential cookies enable basic functions and are necessary for the proper function of the website.',
                    'Frontend / Cookie Groups / Text',
                    'borlabs-cookie'
                )
            ) . "',
            1,
            1,
            1,
            1
        ),
        (
            'statistics',
            '" . esc_sql($language) . "',
            '" . esc_sql(_x('Statistics', 'Frontend / Cookie Groups / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(
                _x(
                    'Statistics cookies collect information anonymously. This information helps us to understand how our visitors use our website.',
                    'Frontend / Cookie Groups / Text',
                    'borlabs-cookie'
                )
            ) . "',
            1,
            2,
            1,
            1
        ),
        (
            'marketing',
            '" . esc_sql($language) . "',
            '" . esc_sql(_x('Marketing', 'Frontend / Cookie Groups / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(
                _x(
                    'Marketing cookies are used by third-party advertisers or publishers to display personalized ads. They do this by tracking visitors across websites.',
                    'Frontend / Cookie Groups / Text',
                    'borlabs-cookie'
                )
            ) . "',
            1,
            3,
            1,
            1
        ),
        (
            'external-media',
            '" . esc_sql($language) . "',
            '" . esc_sql(_x('External Media', 'Frontend / Cookie Groups / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(
                _x(
                    'Content from video platforms and social media platforms is blocked by default. If External Media cookies are accepted, access to those contents no longer requires manual consent.',
                    'Frontend / Cookie Groups / Text',
                    'borlabs-cookie'
                )
            ) . "',
            1,
            4,
            1,
            1
        )
        ON DUPLICATE KEY UPDATE
            `undeletable` = VALUES(`undeletable`)
        ";
    }

    /**
     * getDefaultEntriesCookies function.
     *
     * @access public
     *
     * @param  mixed  $tableName
     * @param  mixed  $language
     * @param  mixed  $tableNameCookieGroups
     *
     */
    public function getDefaultEntriesCookies($tableName, $language, $tableNameCookieGroups)
    {
        global $wpdb;

        // Get Cookie Group Ids
        $cookieGroupIds = [];

        $cookieGroups = $wpdb->get_results(
            "
            SELECT
                `id`,
                `group_id`
            FROM
                `" . $tableNameCookieGroups . "`
            WHERE
                `language` = '" . esc_sql($language) . "'
        "
        );

        foreach ($cookieGroups as $groupData) {
            $cookieGroupIds[$groupData->group_id] = $groupData->id;
        }

        return "INSERT INTO `" . $tableName . "`
        (
            `cookie_id`,
            `language`,
            `cookie_group_id`,
            `service`,
            `name`,
            `provider`,
            `purpose`,
            `privacy_policy_url`,
            `hosts`,
            `cookie_name`,
            `cookie_expiry`,
            `opt_in_js`,
            `settings`,
            `position`,
            `status`,
            `undeletable`
        )
        VALUES
        (
            'borlabs-cookie',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['essential']) . "',
            'Custom',
            'Borlabs Cookie',
            '" . esc_sql(_x('Owner of this website', 'Frontend / Cookie / Borlabs Cookie / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(
                _x(
                    'Saves the visitors preferences selected in the Cookie Box of Borlabs Cookie.',
                    'Frontend / Cookie / Borlabs Cookie / Text',
                    'borlabs-cookie'
                )
            ) . "',
            '',
            '" . esc_sql(serialize([])) . "',
            'borlabs-cookie',
            '" . esc_sql(_x('1 Year', 'Frontend / Cookie / Borlabs Cookie / Text', 'borlabs-cookie')) . "',
            '',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            1,
            1,
            1
        ),
        (
            'facebook',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'Facebook',
            'Facebook',
            '" . esc_sql(
                _x('Used to unblock Facebook content.', 'Frontend / Cookie / Facebook / Name', 'borlabs-cookie')
            ) . "',
            '" . esc_sql(
                _x(
                    'https://www.facebook.com/privacy/explanation',
                    'Frontend / Cookie / Facebook / Text',
                    'borlabs-cookie'
                )
            ) . "',
            '" . esc_sql(serialize(['.facebook.com'])) . "',
            '',
            '',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("facebook"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            1,
            1,
            0
        ),
        (
            'googlemaps',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'Google Maps',
            'Google',
            '" . esc_sql(
                _x('Used to unblock Google Maps content.', 'Frontend / Cookie / Google Maps / Name', 'borlabs-cookie')
            ) . "',
            '" . esc_sql(
                _x(
                    'https://policies.google.com/privacy?hl=en&gl=en',
                    'Frontend / Cookie / Google Maps / Text',
                    'borlabs-cookie'
                )
            ) . "',
            '" . esc_sql(serialize(['.google.com'])) . "',
            'NID',
            '" . esc_sql(_x('6 Month', 'Frontend / Cookie / Google Maps / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("googlemaps"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            2,
            1,
            0
        ),
        (
            'instagram',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'Instagram',
            'Facebook',
            '" . esc_sql(
                _x('Used to unblock Instagram content.', 'Frontend / Cookie / Instagram / Name', 'borlabs-cookie')
            ) . "',
            '" . esc_sql(
                _x('https://www.instagram.com/legal/privacy/', 'Frontend / Cookie / Instagram / Text', 'borlabs-cookie')
            ) . "',
            '" . esc_sql(serialize(['.instagram.com'])) . "',
            'pigeon_state',
            '" . esc_sql(_x('Session', 'Frontend / Cookie / Instagram / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("instagram"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            3,
            1,
            0
        ),
        (
            'openstreetmap',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'OpenStreetMap',
            'OpenStreetMap Foundation',
            '" . esc_sql(
                _x(
                    'Used to unblock OpenStreetMap content.',
                    'Frontend / Cookie / OpenStreetMap / Name',
                    'borlabs-cookie'
                )
            ) . "',
            '" . esc_sql(
                _x(
                    'https://wiki.osmfoundation.org/wiki/Privacy_Policy',
                    'Frontend / Cookie / OpenStreetMap / Text',
                    'borlabs-cookie'
                )
            ) . "',
            '" . esc_sql(serialize(['.openstreetmap.org'])) . "',
            '_osm_location, _osm_session, _osm_totp_token, _osm_welcome, _pk_id., _pk_ref., _pk_ses., qos_token',
            '" . esc_sql(_x('1-10 Years', 'Frontend / Cookie / OpenStreetMap / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("openstreetmap"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            4,
            1,
            0
        ),
        (
            'twitter',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'Twitter',
            'Twitter',
            '" . esc_sql(_x('Used to unblock Twitter content.', 'Frontend / Cookie / Twitter / Name', 'borlabs-cookie'))
            . "',
            '" . esc_sql(_x('https://twitter.com/privacy', 'Frontend / Cookie / Twitter / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(serialize(['.twimg.com', '.twitter.com'])) . "',
            '__widgetsettings, local_storage_support_test',
            '" . esc_sql(_x('Unlimited', 'Frontend / Cookie / Twitter / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("twitter"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            5,
            1,
            0
        ),
        (
            'vimeo',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'Vimeo',
            'Vimeo',
            '" . esc_sql(_x('Used to unblock Vimeo content.', 'Frontend / Cookie / Twitter / Name', 'borlabs-cookie')) . "',
            '" . esc_sql(_x('https://vimeo.com/privacy', 'Frontend / Cookie / Twitter / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(serialize(['player.vimeo.com'])) . "',
            'vuid',
            '" . esc_sql(_x('2 Years', 'Frontend / Cookie / Twitter / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("vimeo"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            6,
            1,
            0
        ),
        (
            'youtube',
            '" . esc_sql($language) . "',
            '" . esc_sql($cookieGroupIds['external-media']) . "',
            'Custom',
            'YouTube',
            'YouTube',
            '" . esc_sql(_x('Used to unblock YouTube content.', 'Frontend / Cookie / YouTube / Name', 'borlabs-cookie'))
            . "',
            '" . esc_sql(
                _x(
                    'https://policies.google.com/privacy?hl=en&gl=en',
                    'Frontend / Cookie / YouTube / Text',
                    'borlabs-cookie'
                )
            ) . "',
            '" . esc_sql(serialize(['google.com'])) . "',
            'NID',
            '" . esc_sql(_x('6 Month', 'Frontend / Cookie / YouTube / Text', 'borlabs-cookie')) . "',
            '" . esc_sql(
                '<script>if(typeof window.BorlabsCookie === "object") { window.BorlabsCookie.unblockContentId("youtube"); }</script>'
            ) . "',
            'a:2:{s:25:\"blockCookiesBeforeConsent\";s:1:\"0\";s:10:\"prioritize\";s:1:\"0\";}',
            7,
            1,
            0
        )
        ON DUPLICATE KEY UPDATE
            `undeletable` = VALUES(`undeletable`)
        ";
    }

    /**
     * installPlugin function.
     *
     * @access public
     */
    public function installPlugin()
    {
        global $wpdb;

        $tableNameCookies = $wpdb->base_prefix . 'borlabs_cookie_cookies';
        $tableNameCookieGroups = $wpdb->base_prefix . 'borlabs_cookie_groups';
        $tableNameCookieConsentLog = $wpdb->base_prefix . 'borlabs_cookie_consent_log';
        $tableNameContentBlocker = $wpdb->base_prefix . 'borlabs_cookie_content_blocker';
        $tableNameScriptBlocker = $wpdb->base_prefix . 'borlabs_cookie_script_blocker';
        $charsetCollate = $wpdb->get_charset_collate();

        $sqlCreateTableCookies = $this->getCreateTableStatementCookies($tableNameCookies, $charsetCollate);
        $sqlCreateTableCookieGroups = $this->getCreateTableStatementCookieGroups(
            $tableNameCookieGroups,
            $charsetCollate
        );
        $sqlCreateTableCookieLog = $this->getCreateTableStatementCookieConsentLog(
            $tableNameCookieConsentLog,
            $charsetCollate
        );
        $sqlCreateTableContentBlocker = $this->getCreateTableStatementContentBlocker(
            $tableNameContentBlocker,
            $charsetCollate
        );
        $sqlCreateTableScriptBlocker = $this->getCreateTableStatementScriptBlocker(
            $tableNameScriptBlocker,
            $charsetCollate
        );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sqlCreateTableCookieGroups);
        dbDelta($sqlCreateTableCookies);
        dbDelta($sqlCreateTableCookieLog);
        dbDelta($sqlCreateTableContentBlocker);
        dbDelta($sqlCreateTableScriptBlocker);

        // Load language package
        load_plugin_textdomain('borlabs-cookie', false, BORLABS_COOKIE_SLUG . '/languages/');

        // Get language of the blog
        if (defined('BORLABS_COOKIE_IGNORE_ISO_639_1') === false) {
            $defaultBlogLanguage = substr(get_option('WPLANG', 'en_US'), 0, 2);
        }

        // Fallback for the case when WPLANG is empty and default value doesn't work
        if (empty($defaultBlogLanguage)) {
            $defaultBlogLanguage = 'en';
        }

        // Load correct DE language file if any DE language was selected
        if (in_array($defaultBlogLanguage, ['de', 'de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal'])) {
            // Load german language pack
            load_textdomain('borlabs-cookie', BORLABS_COOKIE_PLUGIN_PATH . 'languages/borlabs-cookie-de_DE.mo');
        }

        // Default entries
        $sqlDefaultEntriesCookieGroups = $this->getDefaultEntriesCookieGroups(
            $tableNameCookieGroups,
            $defaultBlogLanguage
        );
        $wpdb->query($sqlDefaultEntriesCookieGroups);

        $sqlDefaultEntriesCookies = $this->getDefaultEntriesCookies(
            $tableNameCookies,
            $defaultBlogLanguage,
            $tableNameCookieGroups
        );
        $wpdb->query($sqlDefaultEntriesCookies);

        // Add user capabilities
        $this->addUserCapabilities();

        update_option('BorlabsCookieVersion', BORLABS_COOKIE_VERSION, 'yes');

        // Add cache folder
        if (! file_exists(WP_CONTENT_DIR . '/cache')) {
            if (is_writable(WP_CONTENT_DIR)) {
                mkdir(WP_CONTENT_DIR . '/cache');
            }
        }

        if (! file_exists(WP_CONTENT_DIR . '/cache/borlabs-cookie')) {
            if (is_writable(WP_CONTENT_DIR . '/cache')) {
                mkdir(WP_CONTENT_DIR . '/cache/borlabs-cookie');
            }
        }

        if (is_multisite()) {
            $allBlogs = $wpdb->get_results(
                "
                SELECT
                    `blog_id`
                FROM
                    `" . $wpdb->base_prefix . "blogs`
            "
            );

            if (! empty($allBlogs)) {
                $originalBlogId = get_current_blog_id();

                foreach ($allBlogs as $blogData) {
                    if ($blogData->blog_id != 1) {
                        switch_to_blog($blogData->blog_id);

                        $tableNameCookies = $wpdb->prefix . 'borlabs_cookie_cookies';
                        $tableNameCookieGroups = $wpdb->prefix
                            . 'borlabs_cookie_groups'; // ->prefix contains base_prefix + blog id
                        $tableNameCookieConsentLog = $wpdb->prefix
                            . 'borlabs_cookie_consent_log'; // ->prefix contains base_prefix + blog id
                        $tableNameContentBlocker = $wpdb->prefix
                            . 'borlabs_cookie_content_blocker'; // ->prefix contains base_prefix + blog id
                        $tableNameScriptBlocker = $wpdb->prefix
                            . 'borlabs_cookie_script_blocker'; // ->prefix contains base_prefix + blog id

                        $sqlCreateTableCookies = $this->getCreateTableStatementCookies(
                            $tableNameCookies,
                            $charsetCollate
                        );
                        $sqlCreateTableCookieGroups = $this->getCreateTableStatementCookieGroups(
                            $tableNameCookieGroups,
                            $charsetCollate
                        );
                        $sqlCreateTableCookieLog = $this->getCreateTableStatementCookieConsentLog(
                            $tableNameCookieConsentLog,
                            $charsetCollate
                        );
                        $sqlCreateTableContentBlocker = $this->getCreateTableStatementContentBlocker(
                            $tableNameContentBlocker,
                            $charsetCollate
                        );
                        $sqlCreateTableScriptBlocker = $this->getCreateTableStatementScriptBlocker(
                            $tableNameScriptBlocker,
                            $charsetCollate
                        );

                        dbDelta($sqlCreateTableCookieGroups);
                        dbDelta($sqlCreateTableCookies);
                        dbDelta($sqlCreateTableCookieLog);
                        dbDelta($sqlCreateTableContentBlocker);
                        dbDelta($sqlCreateTableScriptBlocker);

                        // Get language of the blog
                        if (defined('BORLABS_COOKIE_IGNORE_ISO_639_1') === false) {
                            $blogLanguage = substr(get_option('WPLANG', 'en_US'), 0, 2);
                        }

                        // Fallback for the case when WPLANG is empty and default value doesn't work
                        if (empty($blogLanguage)) {
                            $blogLanguage = 'en';
                        }

                        if (
                            in_array($blogLanguage, ['de', 'de_DE', 'de_DE_formal', 'de_AT', 'de_CH', 'de_CH_informal'])
                        ) {
                            // Load german language pack
                            load_textdomain(
                                'borlabs-cookie',
                                BORLABS_COOKIE_PLUGIN_PATH . 'languages/borlabs-cookie-de_DE.mo'
                            );
                        } else {
                            // Load unload language pack
                            unload_textdomain('borlabs-cookie');
                        }

                        // Default entries
                        $sqlDefaultEntriesCookieGroups = $this->getDefaultEntriesCookieGroups(
                            $tableNameCookieGroups,
                            $blogLanguage
                        );
                        $wpdb->query($sqlDefaultEntriesCookieGroups);

                        $sqlDefaultEntriesCookies = $this->getDefaultEntriesCookies(
                            $tableNameCookies,
                            $defaultBlogLanguage,
                            $tableNameCookieGroups
                        );
                        $wpdb->query($sqlDefaultEntriesCookies);

                        // Default Content Blocker
                        ContentBlocker::getInstance()->initDefault();

                        // Add user capabilities
                        $this->addUserCapabilities();

                        update_option('BorlabsCookieVersion', BORLABS_COOKIE_VERSION, 'yes');
                    }
                }

                switch_to_blog($originalBlogId);
            }
        }

        // On Multisite Networks the ContentBlocker class will have the table of the current
        // instance and not of the main instance. Because of that, the table which is used by
        // this class if available during the is_multisite() install routine so we have to wait
        // for its creation first.

        // Default Content Blocker
        ContentBlocker::getInstance()->initDefault();
    }

    /**
     * removeUserCapabilities function.
     *
     * @access public
     */
    public function removeUserCapabilities()
    {
        global $wp_roles;

        if (! isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $capabilities = $this->getCapabilities();

        foreach ($capabilities as $cap) {
            $wp_roles->remove_cap('administrator', $cap);
        }
    }
}
