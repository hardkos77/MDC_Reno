<?php

/**
 *
 * @package Duplicator
 * @copyright (c) 2021, Snapcreek LLC
 *
 */

namespace Duplicator\Libs\Snap;

use Duplicator\Installer\Utils\Log\Log;
use Exception;
use ReflectionClass;
use ReflectionObject;

/**
 * This class serializes and deserializes a variable in json keeping the class type and saving also private objects
 */
class SnapJsonSerialize
{
    const CLASS_KEY_FOR_JSON_SERIALIZE = '==_CLASS_==_NAME_==';

    /**
     * Return json string
     *
     * @param mixed $value value to serialize
     *
     * @return string
     */
    public static function serialize($value)
    {
        return SnapJson::jsonEncodePPrint(self::parseValueToArray($value));
    }

    /**
     * Return json string, equivalent to serialize, but with the possibility to skip some properties of object
     * Accept only object
     *
     * @param object $obj       object to serialize
     * @param array  $skipProps properties to skip
     *
     * @return string
     */
    public static function serializeObj($obj, $skipProps = array())
    {
        if (!is_object($obj)) {
            throw new Exception('invalid obj param');
        }

        return SnapJson::jsonEncodePPrint(self::objectToPublicArrayClass($obj, array(), $skipProps));
    }

    /**
     * Unserialize from json
     *
     * @param string $json json string
     *
     * @return mixed
     */
    public static function unserialize($json)
    {
        $publicArray = json_decode($json, true);
        return self::parseArrayToValue($publicArray);
    }

    /**
     * Unserialize json on passed object
     *
     * @param string $json json string
     * @param object $obj  object to fill
     *
     * @return object
     */
    public static function unserializeToObject($json, $obj)
    {
        if (!is_object($obj)) {
            throw new Exception('invalid obj param');
        }

        $result = self::parseArrayToValue(json_decode($json, true), $obj);
        if ($result !== $obj) {
            throw new Exception('invalid obj param');
        }
    }

    /**
     * Convert object to array includere private proprieties
     *
     * @param object   $obj        obejct to serialize
     * @param string[] $objParents objs parents unique hash ids
     * @param array    $skipProps  properties to skip
     *
     * @return array
     */
    protected static function objectToPublicArrayClass($obj, $objParents = array(), $skipProps = array())
    {
        $reflect = new ReflectionObject($obj);
        $result  = array(
            self::CLASS_KEY_FOR_JSON_SERIALIZE => $reflect->name
        );

        if (is_subclass_of($obj, '\\Duplicator\\Libs\\Snap\\AbstractSnapJsonSerializable')) {
            $skipProps = array_unique(array_merge($skipProps, $obj->jsonSleep()));
        }

        /**
         * get all props of current class but not props private of parent class
         */
        foreach ($reflect->getProperties() as $prop) {
            $prop->setAccessible(true);
            $propName  = $prop->getName();
            if ($prop->isStatic() || in_array($propName, $skipProps)) {
                continue;
            }
            $propValue = $prop->getValue($obj);
            $result[$propName] = self::parseValueToArray($propValue, $objParents);
        }

        return $result;
    }

    /**
     * Recursive parse values, all aboejct are transformed to array
     *
     * @param mixed    $value      valute to parse
     * @param string[] $objParents objs parents unique hash ids
     *
     * @return mixed
     */
    protected static function parseValueToArray($value, $objParents = array())
    {
        if (is_scalar($value) || is_null($value)) {
            return $value;
        } elseif (is_object($value)) {
            $objHash = spl_object_hash($value);
            if (in_array($objHash, $objParents)) {
                // prevent recursion
                /** @todo store recursion in serialized json and restore it */
                return null;
            }
            $objParents[] = $objHash;
            return self::objectToPublicArrayClass($value, $objParents);
        } elseif (is_array($value)) {
            $result = array();
            foreach ($value as $key => $arrayVal) {
                $result[$key] = self::parseValueToArray($arrayVal, $objParents);
            }
            return $result;
        } else {
            return $value;
        }
    }

    /**
     * Return value from parsed array
     *
     * @param array       $value  value
     * @param object|null $newObj if is new create new object of fill the object param
     *
     * @return mixed
     */
    protected static function parseArrayToValue($value, $newObj = null)
    {
        if (is_scalar($value) || is_null($value)) {
            return $value;
        } elseif (($newClassName = self::getClassFromArray($value)) !== false) {
            if (is_object($newObj)) {
                // use the passed object as a parameter instead of creating a new one
            } elseif (class_exists($newClassName)) {
                if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                    $classReflect = new ReflectionClass($newClassName);
                    $newObj = $classReflect->newInstanceWithoutConstructor();
                } else {
                    $newObj = new $newClassName();
                }
            } else {
                $newObj = new \StdClass();
            }

            if ($newObj instanceof \stdClass) {
                $excludeProps = array(self::CLASS_KEY_FOR_JSON_SERIALIZE);

                foreach ($value as $arrayProp => $arrayValue) {
                    if (in_array($arrayProp, $excludeProps)) {
                        continue;
                    }
                    $newObj->{$arrayProp} = self::parseArrayToValue($arrayValue);
                }
            } else {
                $reflect = new ReflectionObject($newObj);
                foreach ($reflect->getProperties() as $prop) {
                    $prop->setAccessible(true);
                    $propName = $prop->getName();
                    if (!isset($value[$propName]) || $prop->isStatic()) {
                        continue;
                    }
                    $prop->setValue($newObj, self::parseArrayToValue($value[$propName]));
                }

                if (is_subclass_of($newObj, '\\Duplicator\\Libs\\Snap\\AbstractSnapJsonSerializable')) {
                    $method = $reflect->getMethod('jsonWakeup');
                    $method->setAccessible(true);
                    $method->invoke($newObj);
                }
            }

            return $newObj;
        } elseif (is_array($value)) {
            $result = array();
            foreach ($value as $key => $arrayVal) {
                $result[$key] = self::parseArrayToValue($arrayVal);
            }
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Return class name from array values
     *
     * @param array $array array data
     *
     * @return bool|string  false if prop not found
     */
    protected static function getClassFromArray($array)
    {
        if (isset($array[self::CLASS_KEY_FOR_JSON_SERIALIZE])) {
            return $array[self::CLASS_KEY_FOR_JSON_SERIALIZE];
        } else {
            return false;
        }
    }
}
