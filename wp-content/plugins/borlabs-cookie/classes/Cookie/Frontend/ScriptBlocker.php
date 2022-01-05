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

use BorlabsCookie\Cookie\Tools;
use stdClass;

class ScriptBlocker
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
     * detectedHandles
     *
     * (default value: [])
     *
     * @var mixed
     * @access private
     */
    private $detectedHandles
        = [
            'matchedSearchPhrase' => [],
            'notMatchedSearchPhrase' => [],
        ];
    /**
     * detectedJavaScriptTags
     *
     * @var mixed
     * @access private
     */
    private $detectedJavaScriptTags
        = [
            'matchedSearchPhrase' => [],
            'notMatchedSearchPhrase' => [],
        ];
    /**
     * scriptBlocker
     *
     * (default value: [])
     *
     * @var mixed
     * @access private
     */
    private $scriptBlocker = [];
    /**
     * searchPhrases
     *
     * (default value: [])
     *
     * @var mixed
     * @access private
     */
    private $searchPhrases = [];
    /**
     * statusScanActive
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $statusScanActive = false;
    /**
     * wordpressIncludesURL
     *
     * (default value: '')
     *
     * @var string
     * @access private
     */
    private $wordpressIncludesURL = '';
    /**
     * wordpressPluginsURL
     *
     * (default value: '')
     *
     * @var string
     * @access private
     */
    private $wordpressPluginsURL = '';
    /**
     * wordpressSiteURL
     *
     * @var mixed
     * @access private
     */
    private $wordpressSiteURL = '';
    /**
     * wordpressThemesURL
     *
     * (default value: '')
     *
     * @var string
     * @access private
     */
    private $wordpressThemesURL = '';

    public function __construct()
    {
        // Check if scan is enabled
        if (get_option('BorlabsCookieScanJavaScripts', false)) {
            // Only scan the selected page
            if (
                ! empty($_POST['borlabsCookie']['scanJavaScripts'])
                || ! empty($_GET['__borlabsCookieScanJavaScripts'])
            ) {
                $this->statusScanActive = true;

                $this->searchPhrases = get_option('BorlabsCookieJavaScriptSearchPhrases', false);
            }
        }

        // Get all active script blocker
        $this->getScriptBlocker();

        $this->wordpressIncludesURL = includes_url();
        $this->wordpressPluginsURL = plugins_url();
        $this->wordpressSiteURL = get_site_url();
        $this->wordpressThemesURL = get_theme_root_uri();
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
     * blockHandles function.
     *
     * @access public
     *
     * @param  mixed  $tag
     * @param  mixed  $handle
     * @param  mixed  $src
     *
     * @return void
     */
    public function blockHandles($tag, $handle, $src)
    {
        if (! empty($this->scriptBlocker)) {
            foreach ($this->scriptBlocker as $data) {
                if (! empty($data->handles)) {
                    if (
                        $handle !== 'borlabs-cookie' && $handle !== 'borlabs-cookie-prioritize'
                        && in_array(
                            $handle,
                            $data->handles
                        )
                    ) {
                        $tag = str_replace(
                            [
                                'text/javascript',
                                'application/javascript',
                                '<script',
                                'src=',
                            ],
                            [
                                'text/template',
                                'text/template',
                                '<script data-borlabs-script-blocker-js-handle="' . $handle
                                . '" data-borlabs-script-blocker-id="' . $data->scriptBlockerId . '"',
                                'data-borlabs-script-blocker-src=',
                            ],
                            $tag
                        );
                    }
                }
            }
        }

        return $tag;
    }

    /**
     * blockJavaScriptTag function.
     *
     * @access public
     *
     * @param  mixed  $tag
     *
     * @return void
     */
    public function blockJavaScriptTag($tag)
    {
        if (! empty($this->scriptBlocker)) {
            foreach ($this->scriptBlocker as $data) {
                if (! empty($data->blockPhrases)) {
                    foreach ($data->blockPhrases as $blockPhrase) {
                        if (
                            strpos($tag[0], $blockPhrase) !== false && strpos($tag[0], 'borlabsCookieConfig') === false
                            && strpos($tag[0], 'borlabsCookiePrioritized') === false
                            && strpos($tag[0], 'borlabsCookieContentBlocker') === false
                        ) {
                            // Detect if script is of type javascript
                            $scriptType = [];
                            preg_match('/\<script([^\>]*)type=("|\')([^"\']*)("|\')/Us', $tag[0], $scriptType);

                            // Only <script>-tags without type attribute or with type attribute text/javascript are JavaScript
                            if (
                                empty($scriptType)
                                || ! empty($scriptType)
                                && (strtolower($scriptType[3]) == 'text/javascript'
                                    || strtolower($scriptType[3]) == 'application/javascript')
                            ) {
                                // Add type attribute if missing
                                if (empty($scriptType)) {
                                    $tag[0] = str_replace('<script', '<script type=\'text/template\'', $tag[0]);
                                } else {
                                    $tag[0] = preg_replace('/text\/javascript/', 'text/template', $tag[0], 1);
                                    $tag[0] = preg_replace('/application\/javascript/', 'text/template', $tag[0], 1);
                                }

                                // Switch type attribute and add data attribute
                                $tag[0] = str_replace(
                                    [
                                        '<script',
                                        ' src=',
                                    ],
                                    [
                                        '<script data-borlabs-script-blocker-id=\'' . $data->scriptBlockerId . '\'',
                                        ' data-borlabs-script-blocker-src=',
                                    ],
                                    $tag[0]
                                );
                            }
                        }
                    }
                }
            }
        }

        return $tag[0];
    }

    /**
     * checkDetectedJavaScriptTags function.
     *
     * @access public
     *
     * @param  mixed  $tag
     *
     * @return void
     */
    public function checkDetectedJavaScriptTags($tag)
    {
        // Detect if script is of type javascript
        $scriptType = [];
        preg_match('/\<script([^\>]*)type=("|\')([^"\']*)("|\')/Us', $tag[0], $scriptType);

        // Only <script>-tags without type attribute or with type attribute text/javascript are JavaScript
        if (
            empty($scriptType)
            || ! empty($scriptType)
            && (strtolower($scriptType[3]) == 'text/javascript'
                || strtolower($scriptType[3]) == 'application/javascript')
        ) {
            $scriptSrc = [];
            preg_match('/<script(.*?)src=("|\')([^"\']*)("|\')/', $tag[0], $scriptSrc);

            $allDetectedHandles = Tools::getInstance()->arrayFlat($this->detectedHandles);

            if (empty($scriptSrc[3]) || ! in_array($scriptSrc[3], $allDetectedHandles)) {
                $searchPhraseMatch = $this->checkForSearchPhraseMatch($tag[0]);

                if ($searchPhraseMatch['matched']) {
                    $this->detectedJavaScriptTags['matchedSearchPhrase'][] = [
                        'matchedPhrase' => $searchPhraseMatch['matchedPhrase'],
                        'scriptTag' => $tag[0],
                    ];
                } else {
                    $this->detectedJavaScriptTags['notMatchedSearchPhrase'][] = [
                        'scriptTag' => $tag[0],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * checkForSearchPhraseMatch function.
     *
     * @access public
     *
     * @param  mixed  $source
     *
     * @return void
     */
    public function checkForSearchPhraseMatch($source)
    {
        $data = [
            'matched' => false,
            'matchedPhrase' => '',
        ];

        if (! empty($this->searchPhrases)) {
            foreach ($this->searchPhrases as $phrase) {
                if (strpos($source, $phrase) !== false) {
                    $data['matched'] = true;
                    $data['matchedPhrase'] = $phrase;

                    break;
                }
            }
        }

        return $data;
    }

    /**
     * detectHandles function.
     *
     * @access public
     *
     * @param  mixed  $tag
     * @param  mixed  $handle
     * @param  mixed  $src
     *
     * @return void
     */
    public function detectHandles($tag, $handle, $src)
    {
        global $wp;

        // Check if scan is enabled
        if ($this->statusScanActive) {
            // Check handle
            $searchPhraseMatch = $this->checkForSearchPhraseMatch($handle);

            $scriptType = '';

            if (strpos($src, $this->wordpressThemesURL) !== false) {
                $scriptType = 'theme';
            } else {
                if (strpos($src, $this->wordpressPluginsURL) !== false) {
                    $scriptType = 'plugin';
                } else {
                    if (strpos($src, $this->wordpressIncludesURL) !== false) {
                        $scriptType = 'core';
                    } else {
                        if (strpos($src, $this->wordpressSiteURL) !== false) {
                            $scriptType = 'other';
                        } else {
                            $scriptType = 'external';
                        }
                    }
                }
            }

            if ($searchPhraseMatch['matched']) {
                $this->detectedHandles['matchedSearchPhrase'][$handle] = [
                    'matchedPhrase' => $searchPhraseMatch['matchedPhrase'],
                    'handle' => $handle,
                    'src' => $src,
                ];
            } else {
                // Fallback - check src
                $searchPhraseMatch = $this->checkForSearchPhraseMatch($src);

                if ($searchPhraseMatch['matched']) {
                    $this->detectedHandles['matchedSearchPhrase'][$handle] = [
                        'matchedPhrase' => $searchPhraseMatch['matchedPhrase'],
                        'handle' => $handle,
                        'src' => $src,
                    ];
                } else {
                    $this->detectedHandles['notMatchedSearchPhrase'][$scriptType][$handle] = [
                        'handle' => $handle,
                        'src' => $src,
                    ];
                }
            }
        }

        return $tag;
    }

    /**
     * detectJavaScriptsTags function.
     *
     * @access public
     * @return void
     */
    public function detectJavaScriptsTags()
    {
        // Check if scan is enabled
        if ($this->statusScanActive) {
            if (Buffer::getInstance()->isBufferActive()) {
                $buffer = &Buffer::getInstance()->getBuffer();

                preg_replace_callback('/<script.*<\/script>/Us', [$this, 'checkDetectedJavaScriptTags'], $buffer);
            }
        }
    }

    /**
     * getScriptBlocker function.
     *
     * @access public
     * @return void
     */
    public function getScriptBlocker()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'borlabs_cookie_script_blocker';

        $scriptBlocker = $wpdb->get_results(
            "
            SELECT
                `script_blocker_id`,
                `handles`,
                `js_block_phrases`
            FROM
                `" . $tableName . "`
            WHERE
                `status` = 1
        "
        );

        if (! empty($scriptBlocker)) {
            foreach ($scriptBlocker as $key => $data) {
                $this->scriptBlocker[$key] = new stdClass;
                $this->scriptBlocker[$key]->scriptBlockerId = $scriptBlocker[$key]->script_blocker_id;
                $this->scriptBlocker[$key]->handles = unserialize($scriptBlocker[$key]->handles);
                $this->scriptBlocker[$key]->blockPhrases = unserialize($scriptBlocker[$key]->js_block_phrases);
            }
        }
    }

    /**
     * handleJavaScriptTagBlocking function.
     *
     * @access public
     * @return void
     */
    public function handleJavaScriptTagBlocking()
    {
        if (Buffer::getInstance()->isBufferActive()) {
            $buffer = &Buffer::getInstance()->getBuffer();

            $buffer = preg_replace_callback('/<script.*<\/script>/Us', [$this, 'blockJavaScriptTag'], $buffer);

            Buffer::getInstance()->endBuffering();
        }
    }

    /**
     * hasScriptBlocker function.
     *
     * @access public
     * @return void
     */
    public function hasScriptBlocker()
    {
        return ! empty($this->scriptBlocker) ? true : false;
    }

    /**
     * isScanActive function.
     *
     * @access public
     * @return void
     */
    public function isScanActive()
    {
        return $this->statusScanActive;
    }

    /**
     * saveDetectedJavaScripts function.
     *
     * @access public
     * @return void
     */
    public function saveDetectedJavaScripts()
    {
        // Check if scan is enabled
        if ($this->statusScanActive) {
            if (
                ! empty($this->detectedHandles['matchedSearchPhrase'])
                || ! empty($this->detectedHandles['notMatchedSearchPhrase'])
                || ! empty($this->detectedJavaScripts['matchedSearchPhrase'])
                || ! empty($this->detectedJavaScripts['notMatchedSearchPhrase'])
            ) {
                update_option(
                    'BorlabsCookieDetectedJavaScripts',
                    [
                        'handles' => $this->detectedHandles,
                        'scriptTags' => $this->detectedJavaScriptTags,
                    ],
                    'no'
                );
            }

            // Disable JavaScript scan
            update_option('BorlabsCookieScanJavaScripts', false, 'no');
        }
    }
}
