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

class Userlike
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
        add_action('borlabsCookie/cookie/edit/template/settings/Userlike', [$this, 'additionalSettingsTemplate']);
        add_action(
            'borlabsCookie/cookie/edit/template/settings/help/Userlike',
            [$this, 'additionalSettingsHelpTemplate']
        );
        add_action('borlabsCookie/cookie/save', [$this, 'save']);
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
     * additionalSettingsHelpTemplate function.
     *
     * @access public
     *
     * @param  mixed  $data
     *
     * @return void
     */
    public function additionalSettingsHelpTemplate($data)
    {
        ?>
        <div class="col-12 col-md-4 rounded-right shadow-sm bg-tips text-light">
            <div class="px-3 pt-3 pb-3 mb-4">
                <h3 class="border-bottom mb-3"><?php
                    _ex('Tips', 'Backend / Global / Tips / Headline', 'borlabs-cookie'); ?></h3>
                <h4><?php
                    _ex(
                        'Where can I find the secret?',
                        'Backend / Cookie / Userlike / Tips / Headline',
                        'borlabs-cookie'
                    ); ?></h4>
                <p><?php
                    _ex(
                        'In Userlike click on <strong>Live Chat &gt; Config &gt; Widgets &gt; <em>Your Widget</em> &gt; Edit Widget &gt; Install &gt; Credentials for Applications</strong>.',
                        'Backend / Cookie / Userlike / Tips / Text',
                        'borlabs-cookie'
                    ); ?></p>
            </div>
        </div>
        <?php
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
        $inputSecret = esc_html(! empty($data->settings['secret']) ? $data->settings['secret'] : '');
        ?>
        <div class="form-group row">
            <label for="secret"
                   class="col-sm-4 col-form-label"><?php
                _ex('Secret', 'Backend / Cookie / Userlike / Label', 'borlabs-cookie'); ?></label>
            <div class="col-sm-8">
                <input type="text" class="form-control form-control-sm d-inline-block w-75 mr-2" id="secret"
                       name="settings[secret]" value="<?php
                echo $inputSecret; ?>"
                       placeholder="<?php
                       _ex('Example', 'Backend / Global / Input Placeholder', 'borlabs-cookie'); ?>: ccd92f8...c61c205"
                       required>
                <span data-toggle="tooltip"
                      title="<?php
                      echo esc_attr_x(
                          'Enter your Userlike secret.',
                          'Backend / Cookie / Userlike / Tooltip',
                          'borlabs-cookie'
                      ); ?>"><i
                        class="fas fa-lg fa-question-circle text-dark"></i></span>
                <div
                    class="invalid-feedback"><?php
                    _ex(
                        'This is a required field and cannot be empty.',
                        'Backend / Global / Validation Message',
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
            'cookieId' => 'userlike',
            'service' => 'Userlike',
            'name' => 'Userlike',
            'provider' => 'Userlike UG',
            'purpose' => _x(
                'In order for the Userlike Widget to work, cookies are stored in the user browser. These cookies are technically necessary and are only filled with data when the chat is used. Before that, they have a purely technical task, in order to enable the offer of a service chat.',
                'Frontend / Cookie / Userlike / Text',
                'borlabs-cookie'
            ),
            'privacyPolicyURL' => _x(
                'https://www.userlike.com/en/terms#privacy-policy',
                'Frontend / Cookie / Userlike / Text',
                'borlabs-cookie'
            ),
            'hosts' => [
                'userlike-cdn-widgets.s3-eu-west-1.amazonaws.com',
            ],
            'cookieName' => 'uslk_e,uslk_s',
            'cookieExpiry' => _x('Session / 1 Year', 'Frontend / Cookie / Userlike / Text', 'borlabs-cookie'),
            'optInJS' => $this->optInJS(),
            'optOutJS' => '',
            'fallbackJS' => '',
            'settings' => [
                'blockCookiesBeforeConsent' => false,
                'prioritize' => false,
                'secret' => '',
            ],
            'status' => true,
            'undeletetable' => false,
        ];

        return $data;
    }

    /**
     * save function.
     *
     * @access public
     *
     * @param  mixed  $formData
     *
     * @return void
     */
    public function save($formData)
    {
        if (! empty($formData['service']) && $formData['service'] === 'Userlike') {
            if (! empty($formData['settings']['secret'])) {
                $formData['settings']['secret'] = trim($formData['settings']['secret']);
            }
        }

        return $formData;
    }

    /**
     * optInJS function.
     *
     * @access private
     * @return void
     */
    private function optInJS()
    {
        $code = <<<EOT
<script type="text/javascript" src="https://userlike-cdn-widgets.s3-eu-west-1.amazonaws.com/%%secret%%.js"></script>
EOT;

        return $code;
    }
}
