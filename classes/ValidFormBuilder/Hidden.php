<?php
namespace ValidFormBuilder;

/**
 * *************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
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
 * @copyright 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 *       *************************
 */

/**
 * Hidden Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 */
class Hidden extends Element
{

    public function __construct($name, $type, $meta = array())
    {
        parent::__construct($name, $type, "", array(), array(), $meta);
    }

    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true)
    {
        $strOutput = "";
        $this->setConditionalMeta();

        $strOutput .= "<input type=\"hidden\" value=\"{$this->__getValue($submitted)}\"
                        name=\"{$this->__name}\" id=\"{$this->__id}\" {$this->__getFieldMetaString()} />\n";

        return $strOutput;
    }

    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        // *** Condition logic.
        $strOutput .= $this->conditionsToJs($intDynamicPosition);

        return $strOutput;
    }

    public function hasFields()
    {
        return false;
    }

    public function isDynamicCounter()
    {
        return $this->__dynamiccounter;
    }

    public function isValid($intCount = null)
    {
        $blnReturn = false;
        $intDynamicCount = ($this->isDynamicCounter()) ? $this->__validator->getValue() : 0;

        for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
            $blnReturn = $this->__validator->validate($intCount);

            if (! $blnReturn) {
                break;
            }
        }

        return $blnReturn;
    }
}
