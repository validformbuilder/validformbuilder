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
 * Adds a SelectOption (<option>) to Select object
 *
 * See {@link \ValidFormBuilder\Select} for examples and usage.
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class SelectOption extends Element
{

    /**
     * The label
     * @internal
     * @var string
     */
    protected $__label;
    /**
     * The value
     * @internal
     * @var string
     */
    protected $__value;
    /**
     * Selected state
     * @internal
     * @var boolean
     */
    protected $__selected;

    /**
     * Create new SelectOption instance
     *
     * @internal
     * @param string $label The label
     * @param string $value The value
     * @param string $selected Set this option to be selected by default or not
     * @param array $meta The meta array
     */
    public function __construct($label, $value, $selected = false, $meta = array())
    {
        if (is_null($meta)) {
            $meta = array();
        }

        $this->__label = $label;
        $this->__value = $value;
        $this->__selected = $selected;
        $this->__meta = $meta;
    }

    /**
     * Generate HTMl output
     *
     * See {@link \ValidFormBuilder\Element::toHtml()}
     *
     * @internal
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtmlInternal($value = null)
    {
        $strSelected = "";
        if ($this->__selected && is_null($value)) {
            $strSelected = " selected=\"selected\"";
        }

        if ($value == $this->__value) {
            $strSelected = " selected=\"selected\"";
        }

        $strOutput = "<option value=\"{$this->__value}\"{$strSelected} {$this->__getMetaString()}>{$this->__label}</option>\n";

        return $strOutput;
    }

    /**
     * Get this option's value
     *
     * @internal
     * @see \ValidFormBuilder\Element::getValue()
     */
    public function getValue($intDynamicPosition = 0)
    {
        return $this->__value;
    }

    /**
     * @internal
     * @see \ValidFormBuilder\Element::__getValue()
     */
    public function __getValue($submitted = false, $intCount = 0)
    {
        $varReturn = parent::__getValue($submitted, $intCount);

        if (is_null($varReturn)) {
            $varReturn = $this->__value;
        }

        return $varReturn;
    }
}
