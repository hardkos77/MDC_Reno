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

namespace BorlabsCookie\Cookie\Frontend\ThirdParty\Themes;

use BorlabsCookie\Cookie\Frontend\Buffer;

class Avada
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
     * adminHeadCSS function.
     *
     * @access public
     * @return void
     */
    public function adminHeadCSS()
    {
        echo "<style>#BorlabsCookieBox { display: none !important; }</style>";
    }

    /**
     * Disable buffer while Avada Live Builder is active. Is required due a bug in Avada.
     */
    public function disableBuffer()
    {
        if (strpos($_SERVER['REQUEST_URI'], 'fb-edit') === false) {
            return;
        }
        if (Buffer::getInstance()->isBufferActive() === false) {
            return;
        }

        $void = Buffer::getInstance()->getBuffer();
        Buffer::getInstance()->endBuffering();
    }
}
