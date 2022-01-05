<?php

/**
 *
 * @package Duplicator
 * @copyright (c) 2021, Snapcreek LLC
 *
 */

namespace Duplicator\Libs\Snap;

abstract class AbstractSnapJsonSerializable extends SnapJsonSerialize implements \JsonSerializable
{
    /**
     * prepared json serialized object
     *
     * @return mixed
     */
    final public function jsonSerialize()
    {
        return self::objectToPublicArrayClass($this, array(), $this->jsonSleep());
    }

    /**
     * this method is similar to the magic __sleep method but instead of returning
     * the list of properties to include it returns the list of properties to exclude
     * @link https://www.php.net/manual/en/language.oop5.magic.php#object.sleep
     *
     * @return strng[]
     */
    protected function jsonSleep()
    {
        return array();
    }

    /**
     * this method is similar to the magic __wakeup method and it
     * is called after the json object has been read
     * @link https://www.php.net/manual/en/language.oop5.magic.php#object.wakeup
     *
     * @return void
     */
    protected function jsonWakeup()
    {
    }
}
