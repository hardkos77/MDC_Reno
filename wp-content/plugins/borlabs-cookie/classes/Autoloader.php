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

namespace BorlabsCookie;

class Autoloader
{

    /**
     * prefixes
     *
     * (default value: [])
     *
     * @var mixed
     * @access protected
     */
    protected $prefixes = [];

    /**
     * register function.
     *
     * @access public
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * addNamespace function.
     *
     * @access public
     * @param mixed $prefix
     * @param mixed $baseDir
     * @param bool $prepend (default: false)
     * @return void
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';

        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend == false) {
            array_push($this->prefixes[$prefix], $baseDir);
        } else {
            array_unshift($this->prefixes[$prefix], $baseDir);
        }
    }

    /**
     * loadClass function.
     *
     * @access public
     * @param mixed $class
     * @return void
     */
    public function loadClass($class)
    {
        $prefix = $class;

        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);

            $relativeClass = substr($class, $pos + 1);

            $fileLoaded = $this->loadFile($prefix, $relativeClass);

            if ($fileLoaded) {
                return $fileLoaded;
            }

            $prefix = rtrim($prefix, '\\');
        }
    }

    /**
     * loadFile function.
     *
     * @access public
     * @param mixed $prefix
     * @param mixed $relativeClass
     * @return void
     */
    public function loadFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        $relativeClass = str_replace('\\', '/', $relativeClass);

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . $relativeClass . '.php';

            if ($this->requireFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * requireFile function.
     *
     * @access public
     * @param mixed $file
     * @return void
     */
    public function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }
}
