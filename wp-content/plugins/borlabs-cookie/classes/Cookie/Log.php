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

class Log
{
    private static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    private $token = null;

    public function __construct()
    {
        $this->token = uniqid();
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
     * Action must be taken immediately.
     *
     * Example: Database down
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function alert($process, $message, array $context = [], array $data = [])
    {
        return $this->log('alert', $process, $message, $context, $data);
    }

    /**
     * Critical conditions.
     *
     * Example: Unexpected condition
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function critical($process, $message, array $context = [], array $data = [])
    {
        return $this->log('critical', $process, $message, $context, $data);
    }

    /**
     * Detailed debug information.
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function debug($process, $message, array $context = [], array $data = [])
    {
        return $this->log('debug', $process, $message, $context, $data);
    }

    /**
     * System is unusable.
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function emergency($process, $message, array $context = [], array $data = [])
    {
        return $this->log('emergency', $process, $message, $context, $data);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function error($process, $message, array $context = [], array $data = [])
    {
        return $this->log('error', $process, $message, $context, $data);
    }

    public function getLogToken()
    {
        return $this->token;
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function info($process, $message, array $context = [], array $data = [])
    {
        return $this->log('info', $process, $message, $context, $data);
    }

    /**
     * interpolate function.
     *
     * @access public
     *
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     *
     * @return void
     */
    public function interpolate($message, array $context = [])
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (! is_array($val) && (! is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Normal but significant events.
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function notice($process, $message, array $context = [], array $data = [])
    {
        return $this->log('notice', $process, $message, $context, $data);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @access public
     *
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    public function warning($process, $message, array $context = [], array $data = [])
    {
        return $this->log('warning', $process, $message, $context, $data);
    }

    /**
     * log function.
     *
     * @access private
     *
     * @param  mixed  $level
     * @param  mixed  $process
     * @param  mixed  $message
     * @param  array  $context  (default: [])
     * @param  array  $data  (default: [])
     *
     * @return void
     */
    private function log($level, $process, $message, array $context = [], array $data = [])
    {
        if (defined('BORLABS_COOKIE_DEBUG') && BORLABS_COOKIE_DEBUG === true) {
            if (! is_array($data) && ! is_object($data)) {
                $data = [$data];
            }

            $message = $this->interpolate($message, $context);

            error_log('[' . $this->getLogToken() . '][' . $level . '] ' . $message);
        }

        return true;
    }
}
