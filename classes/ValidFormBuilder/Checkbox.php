<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@cattlea.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@cattlea.com>
 * @copyright 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */

namespace ValidFormBuilder;

/**
 * Create checkboxes (boolean fields)
 *
 * #### Example
 * ```php
 * // The following code adds a \ValidFormBuilder\Checkbox element to the forms elements collection.
 * $objForm->addField("agree", "I agree to the terms and conditions.", ValidForm::VFORM_BOOLEAN);
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@cattlea.com>
 * @version Release: 3.0.0
 */
class Checkbox extends Element
{

    /**
     * Generate HTML output
     *
     * @see \ValidFormBuilder\Element::toHtml() Element::toHtml() for a full description of this method
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        $blnError = ($submitted && ! $this->__validator->validate() && $blnDisplayErrors) ? true : false;

        if (! $blnSimpleLayout) {
            // *** We asume that all dynamic fields greater than 0 are never required.
            if ($this->__validator->getRequired()) {
                $this->setMeta("class", "vf__required");
            } else {
                $this->setMeta("class", "vf__optional");
            }

            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            if (! $blnLabel) {
                $this->setMeta("class", "vf__nolabel");
            }

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";

            if ($blnError) {
                $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError()}</p>";
            }

            if ($this->__getValue($submitted)) {
                // *** Add the "checked" attribute to the input field.
                $this->setFieldMeta("checked", "checked");
            } else {
                // *** Remove the "checked" attribute from the input field. Just to be sure it wasn't set before.
                $this->setFieldMeta("checked", null, true);
            }

            if ($blnLabel) {
                $strLabel = (! empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
                if (! empty($this->__label)) {
                    $strOutput .= "<label for=\"{$this->__id}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
                }
            }
        } else {
            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            $this->setMeta("class", "vf__multifielditem");

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";

            if ($this->__getValue($submitted)) {
                // *** Add the "checked" attribute to the input field.
                $this->setFieldMeta("checked", "checked");
            } else {
                // *** Remove the "checked" attribute from the input field. Just to be sure it wasn't set before.
                $this->setFieldMeta("checked", null, true);
            }
        }

        $strOutput .= "<input type=\"checkbox\" name=\"{$this->__name}\" id=\"{$this->__id}\"{$this->__getFieldMetaString()}/>\n";

        if (! empty($this->__tip)) {
            $this->setTipMeta("class", "vf__tip");
            
            $strOutput .= "<small{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        $strOutput .= "</div>\n";

        return $strOutput;
    }

    /**
     * Generate Javascript
     * @see \ValidFormBuilder\Element::toJS() Element::toJS() for a full description of this method
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        $strCheck = $this->__sanitizeCheckForJs($this->__validator->getCheck());
        $strRequired = ($this->__validator->getRequired()) ? "true" : "false";
        ;
        $intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
        $intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

        $strOutput .= "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '"
            . addslashes((string)$this->__validator->getFieldHint()) . "', '" . addslashes((string)$this->__validator->getTypeError()) . "', '"
            . addslashes((string)$this->__validator->getRequiredError()) . "', '" . addslashes((string)$this->__validator->getHintError()) . "', '"
            . addslashes((string)$this->__validator->getMinLengthError()) . "', '" . addslashes((string)$this->__validator->getMaxLengthError()) . "');\n";

        // *** Condition logic.
        $strOutput .= $this->conditionsToJs($intDynamicPosition);

        return $strOutput;
    }

    /**
     * Get checkbox value
     *
     * See {@link \ValidFormBuilder\Element::getValue()}
     *
     * @see \ValidFormBuilder\Element::getValue()
     */
    public function getValue($intDynamicPosition = 0)
    {
        $varValue = parent::getValue($intDynamicPosition);
        return (strlen((string)$varValue) > 0 && $varValue !== 0) ? true : false;
    }

    /**
     * Get default value
     * See {@link \ValidFormBuilder\Element::getDefault()}
     *
     * @see \ValidFormBuilder\Element::getDefault()
     */
    public function getDefault($intDynamicPosition = 0)
    {
        return (strlen((string)$this->__default) > 0 && $this->getValue($intDynamicPosition)) ? "on" : null;
    }
}
