<?php
namespace ValidFormBuilder;

/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright 2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://code.google.com/p/validformbuilder/
 */

/**
 * String Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.3
 *
 */
class String extends Base
{

    protected $__id;

    protected $__body;

    protected $__dynamiccounter = false;

    public function __construct($bodyString, $meta = array())
    {
        $this->__body = $bodyString;
        $this->__meta = $meta;
    }

    public function toHtml($submitted = false, $blnSimpleLayout = false)
    {
        return $this->__toHtml($submitted, $blnSimpleLayout);
    }

    public function __toHtml($submitted = false, $blnSimpleLayout = false)
    {
        $strOutput = "";

        if (! $blnSimpleLayout) {
            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = str_replace("[[metaString]]", $this->__getMetaString(), $this->__body);
        } else {
            $this->setMeta("class", "vf__multifielditem");
            $strOutput = "<div " . $this->__getMetaString() . "><span>{$this->__body}</span></div>\n";
        }

        return $strOutput;
    }

    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        if ($this->getMeta("id")) {
            $strId = $this->getMeta("id");

            $strOutput = "objForm.addElement('{$strId}', '{$strId}');\n";

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs($intDynamicPosition);
        }

        return $strOutput;
    }

    public function isValid()
    {
        return true;
    }

    public function hasFields()
    {
        return false;
    }

    public function getValue()
    {
        return;
    }

    public function getValidator()
    {
        return null;
    }

    public function getName()
    {
        return;
    }

    public function getData($strKey = null)
    {
        return;
    }

    public function isDynamic()
    {
        return false;
    }
}
