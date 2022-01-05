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

class ContentBlocker
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    private $cacheFolder = '';
    private $contentBlocker = [];
    private $currentBlockedContent = '';
    private $currentTitle = '';
    private $currentURL = '';
    private $defaultClassMapping
        = [
            'facebook' => 'Facebook',
            'default' => 'Fallback', // Default
            'googlemaps' => 'GoogleMaps',
            'instagram' => 'Instagram',
            'openstreetmap' => 'OpenStreetMap',
            'twitter' => 'Twitter',
            'vimeo' => 'Vimeo',
            'youtube' => 'YouTube',
        ];
    private $hostWhitelist = [];
    private $hosts = [];
    private $siteHost = '';

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->init();
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
     * detectIframes function.
     *
     * @access public
     *
     * @param  mixed  $content
     * @param  mixed  $postId  (default: null)
     * @param  mixed  $field  (default: null)
     *
     * @return void
     */
    public function detectIframes($content, $postId = null, $field = null)
    {
        if (function_exists('is_feed') && is_feed()) {
            if (Config::getInstance()->get('removeIframesInFeeds') == true) {
                $content = preg_replace('/(\<p\>)?(<iframe.*?(?=<\/iframe>)<\/iframe>){1}(\<\/p\>)?/is', '', $content);
            }
        } else {
            $content = preg_replace_callback(
                '/(\<p\>)?(<iframe.*?(?=<\/iframe>)<\/iframe>){1}(\<\/p\>)?/is',
                [$this, 'handleIframe'],
                $content
            );
        }

        return $content;
    }

    /**
     * getCacheFolder function.
     *
     * @access public
     * @return void
     */
    public function getCacheFolder()
    {
        return $this->cacheFolder;
    }

    /**
     * getContentBlockerData function.
     *
     * @access public
     *
     * @param  mixed  $contentBlockerId
     *
     * @return void
     */
    public function getContentBlockerData($contentBlockerId)
    {
        if (! empty($this->contentBlocker[$contentBlockerId])) {
            return $this->contentBlocker[$contentBlockerId];
        } else {
            return false;
        }
    }

    /**
     * getCurrentBlockedContent function.
     *
     * @access public
     * @return void
     */
    public function getCurrentBlockedContent()
    {
        return $this->currentBlockedContent;
    }

    /**
     * setCurrentBlockedContent function.
     *
     * @access public
     *
     * @param  mixed  $content
     *
     * @return void
     */
    public function setCurrentBlockedContent($content)
    {
        $this->currentBlockedContent = $content;

        return true;
    }

    /**
     * getCurrentTitle function.
     *
     * @access public
     * @return void
     */
    public function getCurrentTitle()
    {
        return $this->currentTitle;
    }

    /**
     * getCurrentURL function.
     *
     * @access public
     * @return void
     */
    public function getCurrentURL()
    {
        return $this->currentURL;
    }

    /**
     * handleContentBlocking function.
     *
     * @access public
     *
     * @param  mixed  $content
     * @param  string  $url  (default: '')
     * @param  string  $contentBlockerId  (default: '')
     * @param  string  $title  (default: '')
     * @param  mixed  $atts  (default: [])
     *
     * @return void
     */
    public function handleContentBlocking($content, $url = '', $contentBlockerId = '', $title = '', $atts = [])
    {
        // Check if host is on the whitelist
        if (empty($url) || $this->isHostWhitelisted($url) !== true) {
            if (function_exists('is_feed') && is_feed() && Config::getInstance()->get('removeIframesInFeeds') == true) {
                $content = '';
            } else {
                // Set currentContent for third party filter that needs the content unmodified
                $this->currentBlockedContent = $content;
                $this->currentURL = ! empty($url) ? $url : '';
                $this->currentTitle = $title;

                $currentURLData = parse_url($this->currentURL);

                $detectedContentBlockerId = null;

                // When $contentBlockerId is set - overwrites the by URL detected content blocker
                if (! empty($contentBlockerId) && ! empty($this->contentBlocker[$contentBlockerId])) {
                    $detectedContentBlockerId = $contentBlockerId;
                } else {
                    // Detect Content Blocker by Host
                    if (! empty($this->hosts) && ! empty($this->currentURL)) {
                        $levenshtein = 0;
                        $currentHost = $currentURLData['host'] . (isset($currentURLData['path'])
                                ? $currentURLData['path'] : '');

                        foreach ($this->hosts as $host => $contentBlocker) {
                            if (strpos($currentHost, $host) !== false) {
                                if (
                                    (empty($levenshtein) && empty($detectedContentBlockerId))
                                    || levenshtein(
                                        $currentHost,
                                        $host
                                    ) < $levenshtein
                                ) {
                                    $levenshtein = levenshtein($currentHost, $host);
                                    $detectedContentBlockerId = $contentBlocker;
                                }
                            }
                        }
                    }
                }

                // Fallback but only if Fallback was not disabled
                if (empty($detectedContentBlockerId) && ! empty($this->contentBlocker['default'])) {
                    $detectedContentBlockerId = 'default';
                }

                // Do not block oEmbed of own blog
                if (! empty($this->currentURL) && strpos($currentURLData['host'], $this->siteHost) !== false) {
                    $detectedContentBlockerId = null;
                }

                if (! empty($detectedContentBlockerId)) {
                    if (has_filter('borlabsCookie/contentBlocker/modify/content/' . $detectedContentBlockerId)) {
                        $content = apply_filters(
                            'borlabsCookie/contentBlocker/modify/content/' . $detectedContentBlockerId,
                            $content,
                            $atts
                        );
                    } elseif (has_filter('borlabsCookie/bct/modify_content/' . $detectedContentBlockerId)) {
                        // Backwards compatibility
                        $content = apply_filters(
                            'borlabsCookie/bct/modify_content/' . $detectedContentBlockerId,
                            $detectedContentBlockerId,
                            $content
                        );
                    } else {
                        $content = ContentBlocker\Custom::getInstance()->modify(
                            $content,
                            $detectedContentBlockerId,
                            $atts
                        );
                    }

                    if (Config::getInstance()->get('cookieBoxIntegration') === 'javascript') {
                        $blockedContent = '<script type="text/template">' . base64_encode(
                                $this->getCurrentBlockedContent()
                            ) . '</script>';
                    } else {
                        $blockedContent = base64_encode($this->getCurrentBlockedContent());
                    }

                    $content = '<div class="BorlabsCookie">' . $content
                        . '<div class="borlabs-hide" data-borlabs-cookie-type="content-blocker" data-borlabs-cookie-id="'
                        . $detectedContentBlockerId . '">' . $blockedContent . '</div></div>';
                }
            }

            // Remove whitespace to avoid WordPress' automatic br- & p-tags
            $content = preg_replace('/[\s]+/mu', ' ', $content);
        }

        return $content;
    }

    /**
     * handleIframe function.
     *
     * @access public
     *
     * @param  mixed  $tags
     *
     * @return void
     */
    public function handleIframe($tags)
    {
        $content = $tags[0];

        if (strpos($tags[0], 'data-borlabs-cookie-iframe-spared') === false) {
            // Detect host
            $srcMatch = [];

            // Test and replace data-src
            if (preg_match('/data-src=("|\')([^"\']{1,})(\1)/i', $tags[2])) {
                if (strpos($tags[2], ' src') === false) {
                    $tags[2] = str_replace('data-src', 'src', $tags[2]);
                    $tags[0] = str_replace('data-src', 'src', $tags[0]);
                }
            }

            preg_match('/src=("|\')([^"\']{1,})(\1)/i', $tags[2], $srcMatch);

            // Skip iframes without src attribute of where src is about:blank
            if (! empty($srcMatch[2]) && $srcMatch[2] !== 'about:blank') {
                $content = $this->handleContentBlocking($tags[0], $srcMatch[2]);
            }
        }

        return $content;
    }

    /**
     * handleOembed function.
     *
     * @access public
     *
     * @param  mixed  $html
     * @param  mixed  $url
     * @param  mixed  $atts
     * @param  mixed  $postId
     *
     * @return void
     */
    public function handleOembed($html, $url, $atts, $postId)
    {
        return $this->handleContentBlocking($html, $url);
    }

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    public function init()
    {
        global $wpdb;

        $this->hostWhitelist = Config::getInstance()->get('contentBlockerHostWhitelist');

        $this->cacheFolder = WP_CONTENT_DIR . '/cache/borlabs-cookie';
        $this->siteHost = parse_url(get_home_url(), PHP_URL_HOST);

        // Check if main cache folders exists
        if (! file_exists($this->cacheFolder)) {
            // Check if /cache folder exists
            if (! file_exists(WP_CONTENT_DIR . '/cache') && is_writable(WP_CONTENT_DIR)) {
                mkdir(WP_CONTENT_DIR . '/cache');
            }

            if (file_exists(WP_CONTENT_DIR . '/cache') && is_writable(WP_CONTENT_DIR . '/cache')) {
                // Create /borlabs-cookie folder
                mkdir($this->cacheFolder);
            }
        }

        $tableName = $wpdb->prefix . 'borlabs_cookie_content_blocker';

        // Load active Content Blocker
        $contentBlocker = $wpdb->get_results(
            "
            SELECT
                `id`,
                `content_blocker_id`,
                `name`,
                `privacy_policy_url`,
                `hosts`,
                `preview_html`,
                `global_js`,
                `init_js`,
                `settings`
            FROM
                `" . $tableName . "`
            WHERE
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
                AND
                `status` = 1
        "
        );

        if (! empty($contentBlocker)) {
            foreach ($contentBlocker as $key => $data) {
                $contentBlocker[$key]->hosts = unserialize($data->hosts);

                // Collect infos about all active Blocked Content Types
                $this->contentBlocker[$data->content_blocker_id] = [
                    'content_blocker_id' => $data->content_blocker_id,
                    'name' => $data->name,
                    'privacyPolicyURL' => $data->privacy_policy_url,
                    'hosts' => $data->hosts,
                    'previewHTML' => $data->preview_html,
                    'globalJS' => $data->global_js,
                    'initJS' => $data->init_js,
                    'settings' => unserialize($data->settings),
                ];

                // Build list of available hosts => Content Blocker Ids for faster detection
                if (! empty($contentBlocker[$key]->hosts)) {
                    foreach ($contentBlocker[$key]->hosts as $host) {
                        $this->hosts[$host] = $data->content_blocker_id;
                    }
                }

                // Add settings, global js, and init js of the Content Blocker
                JavaScript::getInstance()->addContentBlocker(
                    $data->content_blocker_id,
                    $data->global_js,
                    $data->init_js,
                    unserialize($data->settings)
                );

                // Register action filter of default Content Blocker classes
                if (! empty($this->defaultClassMapping[$data->content_blocker_id])) {
                    $className = '\BorlabsCookie\Cookie\Frontend\ContentBlocker\\'
                        . $this->defaultClassMapping[$data->content_blocker_id];
                    add_filter(
                        'borlabsCookie/contentBlocker/modify/content/' . $data->content_blocker_id,
                        [$className::getInstance(), 'modify'],
                        100,
                        2
                    );
                }
            }
        }

        // Add hosts of disabled Content Blocker to whitelist
        $disabledContentBlocker = $wpdb->get_results(
            "
            SELECT
                `hosts`
            FROM
                `" . $tableName . "`
            WHERE
                `language` = '" . esc_sql(Multilanguage::getInstance()->getCurrentLanguageCode()) . "'
                AND
                `status` = 0
        "
        );

        if (! empty($disabledContentBlocker)) {
            foreach ($disabledContentBlocker as $data) {
                $hosts = unserialize($data->hosts);

                if (! empty($hosts)) {
                    $this->hostWhitelist = array_merge($this->hostWhitelist, unserialize($data->hosts));
                }
            }
        }
    }

    /**
     * isHostWhitelisted function.
     *
     * @access public
     *
     * @param  mixed  $host
     *
     * @return true: host is whitelisted, false: host is not whitelisted
     */
    public function isHostWhitelisted($host)
    {
        $status = false;

        if (! empty($this->hostWhitelist)) {
            foreach ($this->hostWhitelist as $whitelistHost) {
                if (strpos($host, $whitelistHost) !== false) {
                    $status = true;
                }
            }
        }

        return $status;
    }
}
