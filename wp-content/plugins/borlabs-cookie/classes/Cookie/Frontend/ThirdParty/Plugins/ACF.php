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

use BorlabsCookie\Cookie\Frontend\ContentBlocker;

class ACF
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
     * handleOembed function.
     *
     * @access public
     *
     * @param  mixed  $html
     * @param  mixed  $id
     * @param  mixed  $atts
     *
     * @return void
     */
    public function handleOembed($html = '', $id = null, $atts = [])
    {
        // Detect URL
        $url = '';
        $match = [];

        if (! empty($html)) {
            preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $html, $match);

            // Let's just hope the first URL is the right one...
            if (! empty($match[0][0])) {
                $url = $match[0][0];
            }

            $html = ContentBlocker::getInstance()->handleContentBlocking($html, $url);
        }

        return $html;
    }

    /**
     * register function.
     *
     * @access public
     * @return void
     */
    public function register()
    {
        add_filter('acf/format_value/type=oembed', [$this, 'handleOembed'], 100, 3);
        add_filter('acf/format_value/type=textarea', [ContentBlocker::getInstance(), 'detectIframes'], 100, 3);
        add_filter('acf_the_content', [ContentBlocker::getInstance(), 'detectIframes'], 100, 3);
    }
}
