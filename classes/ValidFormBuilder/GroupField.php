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
 * Adds a Checkbox or Radio button to Group element
 *
 * See {@link \ValidFormBuilder\Group} for examples and usage.
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class GroupField extends Element
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
    protected $__checked;

    /**
     * Construct new element
     * @internal
     * @param string $id
     * @param string $name
     * @param integer $type
     * @param string $label
     * @param string $value
     * @param boolean $checked
     * @param array $meta
     */
    public function __construct($id, $name, $type, $label, $value, $checked = false, $meta = array())
    {
        parent::__construct($name, $type, $label, array(), array(), $meta);

        $this->__id = $id;
        $this->__value = $value;
        $this->__checked = $checked;
    }

    /**
     * Generate HTML output
     *
     * See {@link \ValidFormBuilder\Element::toHtml()}
     *
     * @internal
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtmlInternal($value = null, $submitted = false, $intCount = 0)
    {
        $strChecked = "";

        if ($this->__type == ValidForm::VFORM_CHECK_LIST) {
            $strName = ($intCount == 0) ? $this->__name : str_replace("[]", "", $this->__name) . "_" . $intCount . "[]";
        } else {
            $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
        }
        $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;

        if (is_array($value)) {
            foreach ($value as $valueItem) {
                if ($valueItem == $this->__value) {
                    $this->setFieldMeta("checked", "checked");
                    break;
                } else {
                    if ($this->__type == ValidForm::VFORM_RADIO_LIST) {
                        // Uncheck all others if this is a radio list
                        $this->setFieldMeta("checked", null, true); // Remove 'checked'
                    }
                }
            }
        } else {
            $blnCheckedSet = false;
            if ($this->__checked && is_null($value) && ! $submitted) {
                $this->setFieldMeta("checked", "checked");
                $blnCheckedSet = true;
            } else {
                $this->setFieldMeta("checked", null, true); // Remove 'checked'
            }

            if ($value == $this->__value || $blnCheckedSet) {
                $this->setFieldMeta("checked", "checked");
            } else {
                $this->setFieldMeta("checked", null, true); // Remove 'checked'
            }
        }

        // *** Convert the Element type to HTML type.
        /* TODO: Refactor to typeToHtmlType method and implement in all element classes. */
        $type = "";
        switch ($this->__type) {
            case ValidForm::VFORM_RADIO_LIST:
                $type = "radio";
                break;
            case ValidForm::VFORM_CHECK_LIST:
                $type = "checkbox";
                break;
        }

        $strOutput = "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>\n";
        $strOutput .= "<input type=\"{$type}\" value=\"{$this->__value}\" name=\"{$strName}\"
                        id=\"{$strId}\"{$this->__getFieldMetaString()}/>{$this->__label}\n";
        $strOutput .= "</label>\n";

        return $strOutput;
    }

    /**
     * Get the value of this specific checkbox / radio button
     *
     * @internal
     * @return string The value
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
