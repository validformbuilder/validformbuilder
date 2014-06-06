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

    /**
     * This method makes sure only the required parameters are passed to the method being called and it makes
     * sure that these parameters are called in the right order.
     * @param ValidForm|ValidWizard|VF_Element|VF_Base $objParent
     * @param string $strMethod The method being validated against
     * @param array $arrData Child data to sanitize
     * @return array An array of parameters for the given method
     */
    public static function sanitizeForParentFingerprint($objParent, $strMethod, $arrData)
    {
        $objReflection = new \ReflectionMethod($objParent, $strMethod);
        $arrParameters = [];

        /* @var $param \ReflectionParameter */
        foreach ($objReflection->getParameters() as $param) {
            $arrParameters[] = $param->name;
        }

        $arrReturn = [];
        foreach ($arrParameters as $strKey => $strKeyName) {
            $arrReturn[$strKeyName] = $arrData[$strKeyName];
        }

        return $arrReturn;
    }

    /**
     * Validate if the given child array has the required keys set.
     * @param array $child
     * @return boolean True if child is valid, false if not.
     */
    public static function isValidChild($child)
    {
        $blnReturn = false;

        if (isset($child["objectType"])) {
            switch ($child["objectType"]) {
            	case "select":
            	    $allowedChildren = ["option", "optionGroup"];
            	    if (!isset($child["children"]) || !is_array($child["children"])) {
            	        break 2;
            	    }

            	    foreach ($child["children"] as $grandChild) {
            	        if (!isset($grandChild["objectType"])
                            || !in_array($grandChild["objectType"], $allowedChildren)
                        ) {
                            break 2;
                        }
            	    }
            	default:
            	    $blnReturn = true;
            }
        }

        return $blnReturn;
        return isset($child["objectType"]);
    }
}
