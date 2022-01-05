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

namespace BorlabsCookie\Cookie\Backend;

use BorlabsCookie\Cookie\Config;
use BorlabsCookie\Cookie\Multilanguage;

class Help
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    private $imagePath;

    public function __construct()
    {
        $this->imagePath = plugins_url('assets/images', realpath(__DIR__ . '/../../'));
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
     * display function.
     *
     * @access public
     * @return void
     */
    public function display()
    {
        $borlabsCookieStatus = Config::getInstance()->get('cookieStatus');
        $statusCacheFolder = SystemCheck::getInstance()->checkCacheFolders();
        $statusSSLSettings = SystemCheck::getInstance()->checkSSLSettings();

        $statusTableContentBlocker = SystemCheck::getInstance()->checkTableContentBlocker();
        $statusTableCookieConsentLog = SystemCheck::getInstance()->checkTableCookieConsentLog();
        $statusTableCookieGroups = SystemCheck::getInstance()->checkTableCookieGroups();
        $statusTableCookies = SystemCheck::getInstance()->checkTableCookies();
        $statusTableScriptBlocker = SystemCheck::getInstance()->checkTableScriptBlocker();

        $statusDefaultContentBlocker = SystemCheck::getInstance()->checkDefaultContentBlocker();
        $statusDefaultCookieGroups = SystemCheck::getInstance()->checkDefaultCookieGroups();
        $statusDefaultCookies = SystemCheck::getInstance()->checkDefaultCookies();

        // Fix Script Blocker Table
        SystemCheck::getInstance()->checkAndFixScriptBlockerTable();

        // Check and change index of log table
        SystemCheck::getInstance()->checkAndChangeCookieConsentLogIndex();

        // Check and change columns of cookie table
        SystemCheck::getInstance()->checkAndChangeCookiesTable();

        $totalConsentLogs = number_format_i18n(SystemCheck::getInstance()->getTotalConsentLogs());
        $consentLogTableSize = number_format_i18n(SystemCheck::getInstance()->getConsentLogTableSize(), 2);

        $language = Multilanguage::getInstance()->getCurrentLanguageCode();

        $loadingIcon = $this->imagePath . '/borlabs-cookie-icon-black.svg';

        include Backend::getInstance()->templatePath . '/help.html.php';
    }
}
