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

class Multilanguage
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

    public function getAvailableLanguagesForChooser()
    {
        if ($this->isLanguagePluginWeglotActive()) {
            $languages = [];
            array_push($languages, [
                'code' => weglot_get_original_language(),
                'name' => $this->getLanguageName(weglot_get_original_language()),
            ]);
            foreach (weglot_get_destination_languages() as $destination) {
                array_push($languages, [
                    'code' => $destination['language_to'],
                    'name' => $this->getLanguageName($destination['language_to']),
                ]);
            }

            return $languages;
        } else {
            return null;
        }
    }

    /**
     * getCurrentLanguageCode function.
     *
     * @access public
     * @return void
     */
    public function getCurrentLanguageCode()
    {
        $currentLanguage = null;

        if ($this->isMultilanguagePluginActive()) {
            // Polylang
            if (function_exists('pll_current_language')) {
                $currentLanguage = pll_current_language();

                // If currentLanguage is still empty, we have to get the default language
                if (empty($currentLanguage)) {
                    $currentLanguage = pll_default_language();

                    // Fallback: Add action to reload Config later. Necessary when the content defines the language
                    if (is_admin() === false) {
                        add_action('pll_language_defined', [$this, 'polylangLanguageDefined']);
                    }
                }
            } elseif ($this->isLanguagePluginWeglotActive()) {
                // Weglot
                if (is_admin()) {
                    if (empty(session_id())) {
                        session_start();
                    }
                    if (isset($_GET['borlabsLang'])) {
                        if (isset(weglot_get_languages_available()[$_GET['borlabsLang']])) {
                            $_SESSION['borlabsLang'] = $_GET['borlabsLang'];
                            $currentLanguage = $_GET['borlabsLang'];
                        }
                    } elseif (isset($_SESSION['borlabsLang'])) {
                        $currentLanguage = $_SESSION['borlabsLang'];
                    } else {
                        $currentLanguage = weglot_get_original_language();
                    }
                } else {
                    $currentLanguage = weglot_get_current_language();
                }
            } else {
                // WPML
                $null = null;
                $currentLanguage = apply_filters('wpml_current_language', $null);
            }

            // Fallback
            if ($currentLanguage === 'all') {
                $currentLanguage = $this->getDefaultLanguageCode();
            }
        } else {
            $currentLanguage = BORLABS_COOKIE_DEFAULT_LANGUAGE;
        }

        return $currentLanguage;
    }

    /**
     * getCurrentLanguageFlag function.
     *
     * @access public
     * @return void
     */
    public function getCurrentLanguageFlag()
    {
        $currentLanguageFlag = '';

        // Get the flag, works with WPML & Polylang
        if ($this->isMultilanguagePluginActive()) {
            $currentLanguageCode = $this->getCurrentLanguageCode();

            $currentLanguageFlag = $this->getLanguageFlag($currentLanguageCode);
        }

        return $currentLanguageFlag;
    }

    /**
     * getCurrentLanguageName function.
     * Only returns the name when WPML/Polylang is active and loaded!
     *
     * @access public
     * @return void
     */
    public function getCurrentLanguageName()
    {
        $currentLanguageName = '';

        // Polylang
        if (function_exists('pll_current_language')) {
            $currentLanguageName = pll_current_language('name');

            // If currentLanguage is still empty, we have to get the default language
            if (empty($currentLanguageName)) {
                $currentLanguageName = pll_default_language('name');
            }
        } elseif ($this->isLanguagePluginWeglotActive()) {
            // Weglot
            if (isset(weglot_get_languages_available()[weglot_get_current_language()])) {
                $currentLanguageName = weglot_get_languages_available()[weglot_get_current_language()]->getLocalName();
            }
        } elseif (defined('ICL_LANGUAGE_NAME')) {
            // WPML
            $currentLanguageName = ICL_LANGUAGE_NAME;
        } else {
            $currentLanguageName = '-';
        }

        return $currentLanguageName;
    }

    /**
     * getDefaultLanguageCode function.
     *
     * @access public
     * @return void
     */
    public function getDefaultLanguageCode()
    {
        $defaultLanguage = null;

        if ($this->isMultilanguagePluginActive()) {
            // Polylang
            if (function_exists('pll_default_language')) {
                $defaultLanguage = pll_default_language();
            } elseif ($this->isLanguagePluginWeglotActive()) {
                // Weglot
                $defaultLanguage = weglot_get_original_language();
            } else {
                // WPML
                $null = null;
                $defaultLanguage = apply_filters('wpml_default_language', $null);
            }
        } else {
            $defaultLanguage = BORLABS_COOKIE_DEFAULT_LANGUAGE;
        }

        return $defaultLanguage;
    }

    /**
     * getLanguageFlag function.
     *
     * @access public
     *
     * @param  mixed  $languageCode
     *
     * @return string
     */
    public function getLanguageFlag($languageCode)
    {
        $languageFlag = '';

        // Get the flag, works with WPML & Polylang
        if ($this->isMultilanguagePluginActive()) {
            if (! $this->isLanguagePluginWeglotActive()) {
                $null = null;
                $listOfActiveLanguages = apply_filters('wpml_active_languages', $null);

                if (! empty($listOfActiveLanguages[$languageCode]['country_flag_url'])) {
                    $languageFlag = $listOfActiveLanguages[$languageCode]['country_flag_url'];
                }
            }
        }

        return $languageFlag;
    }

    /**
     * getLanguageName function.
     *
     * @access public
     *
     * @param  mixed  $languageCode
     *
     * @return void
     */
    public function getLanguageName($languageCode)
    {
        $languageName = '';

        // WPML & Polylang
        if ($this->isMultilanguagePluginActive()) {
            if ($this->isLanguagePluginWeglotActive()) {
                // Weglot
                if (isset(weglot_get_languages_available()[$languageCode])) {
                    $languageName = weglot_get_languages_available()[$languageCode]->getLocalName();
                }
            } else {
                $null = null;
                $languages = apply_filters('wpml_active_languages', $null, []);

                if (! empty($languages[$languageCode])) {
                    $languageName = $languages[$languageCode]['native_name'];
                }
            }
        }

        return $languageName;
    }

    public function isLanguagePluginWeglotActive()
    {
        return function_exists('weglot_get_current_language');
    }

    /**
     * isMultilanguagePluginActive function.
     *
     * @access public
     * @return bool
     */
    public function isMultilanguagePluginActive()
    {
        $status = false;

        if (
            defined('ICL_LANGUAGE_CODE') || defined('POLYLANG_FILE')
            || $this->isLanguagePluginWeglotActive()
        ) {
            $status = true;
        }

        return $status;
    }

    public function needsLanguageChooser()
    {
        return $this->isLanguagePluginWeglotActive();
    }

    /**
     * polylangLanguageDefined function.
     *
     * @access public
     *
     * @param  mixed  $languageCode
     *
     * @return void
     */
    public function polylangLanguageDefined($languageCode)
    {
        // Load config with new language code
        Config::getInstance()->loadConfig($languageCode);

        // Load Content Blocker settings with new language code
        Frontend\ContentBlocker::getInstance()->init();
    }
}
