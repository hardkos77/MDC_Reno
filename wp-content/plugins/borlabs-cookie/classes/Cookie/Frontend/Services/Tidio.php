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

namespace BorlabsCookie\Cookie\Frontend\Services;

class Tidio
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
        add_action('borlabsCookie/cookie/edit/template/settings/Tidio', [$this, 'additionalSettingsTemplate']);
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
     * additionalSettingsTemplate function.
     *
     * @access public
     *
     * @param  mixed  $data
     *
     * @return void
     */
    public function additionalSettingsTemplate($data)
    {
        ?>
        <div class="form-group row">
            <label
                class="col-sm-4 col-form-label"><?php
                _ex('Integration', 'Backend / Cookie / Tidio / Label', 'borlabs-cookie'); ?></label>
            <div class="col-sm-8">
                <div
                    class="alert alert-info mt-2"><?php
                    _ex(
                        'In Tidio click on <strong>Channels &gt; Live chat &gt; Integration &gt; JavaScript</strong>, copy the JavaScript and paste it into the <strong>Opt-in Code</strong> field below.',
                        'Backend / Cookie / Tidio / Text',
                        'borlabs-cookie'
                    ); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * getDefault function.
     *
     * @access public
     * @return void
     */
    public function getDefault()
    {
        $data = [
            'cookieId' => 'tidio',
            'service' => 'Tidio',
            'name' => 'Tidio',
            'provider' => 'Tidio LLC',
            'purpose' => _x(
                'This website is using Tidio, a chat platform that connects users with the customer support of our website. The personal data you enter within the chat are stored within the Tidio application.',
                'Frontend / Cookie / Tidio / Text',
                'borlabs-cookie'
            ),
            'privacyPolicyURL' => _x(
                'https://www.tidio.com/privacy-policy/',
                'Frontend / Cookie / Tidio / Text',
                'borlabs-cookie'
            ),
            'hosts' => [
                '*.tidio.co, *.tidiochat.com',
            ],
            'cookieName' => 'tidio_state_*',
            'cookieExpiry' => _x(
                'Until the user deletes the local storage.',
                'Frontend / Cookie / Tidio / Text',
                'borlabs-cookie'
            ),
            'optInJS' => '',
            'optOutJS' => '',
            'fallbackJS' => '',
            'settings' => [
                'blockCookiesBeforeConsent' => false,
                'prioritize' => false,
            ],
            'status' => true,
            'undeletetable' => false,
        ];

        return $data;
    }
}
