<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@neverwoods.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */
namespace ValidFormBuilder;

/**
 * ClassDynamic Class
 *
 * This class creates a magic get, set and call method. It is extended by all ValidForm Builder classes.
 *
 * @internal
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 *
 * ## CHANGELOG ##
 * - Removed all 'echo'-s and replaced them with throw new BadMethodCallException
 */
class ClassDynamic
{

    /**
     * Magic getter method
     * @internal
     * @param string $property
     * @throws \BadMethodCallException
     */
    public function __get($property)
    {
        $property = strtolower("__" . $property);

        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new \BadMethodCallException(
                "Property Error in " . get_class($this) . "::get({$property}) on line " . __LINE__ . "."
            );
        }
    }

    /**
     * Magic setter method
     * @internal
     * @param string $property
     * @param mixed $value
     * @throws \BadMethodCallException
     */
    public function __set($property, $value)
    {
        $property = strtolower("__" . $property);

        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new \BadMethodCallException(
                "Property Error in " . get_class($this) . "::set({$property}, {$value}) on line " . __LINE__ . "."
            );
        }
    }

    /**
     * Magic caller method
     * @internal
     * @param string $method
     * @param mixed $values
     * @throws \BadMethodCallException
     */
    public function __call($method, $values)
    {
        if (substr($method, 0, 3) == "get") {
            $property = substr($method, 3);
            return $this->$property;
        }

        if (substr($method, 0, 3) == "set") {
            $property = substr($method, 3);
            $this->$property = $values[0];
            return;
        }

        throw new \BadMethodCallException(
            "Method Error in " . get_class($this) . "::{$method} on line " . __LINE__ . "."
        );
    }
}
