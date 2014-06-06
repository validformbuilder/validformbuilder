<?php
namespace ValidFormBuilder;

/**
 * *************************
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
 *       *************************
 */

/**
 * FormArrayValidator Class
 *
 * @package ValidForm
 * @author Robin van Baalen
 */
class FormArrayValidator
{
    /**
     * Validate if the input array is a valid array to generate a ValidForm object from.
     *
     * @param array $arr The form array to validate
     * @return boolean True if the array is valid to work with (e.g. contains the 'form' key) false if not. Default: false
     */
    public static function isValid($formArray)
    {
        $blnReturn = false;

        if (array_key_exists("form", $formArray) && is_array($formArray["form"])) {
            $blnReturn = true;
        }

        return $blnReturn;
    }
}
