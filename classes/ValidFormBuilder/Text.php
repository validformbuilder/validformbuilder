<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2025 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@stylr.nl>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @copyright 2009-2025 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */
namespace ValidFormBuilder;

/**
 * Text Class is the most used.
 *
 * Text objects are used to create input[type='text'] fields.
 *
 * #### Example; Add a text field with some validation and custom classes to the form.
 * ```php
 * $objForm->addField(
 *     "first-name",
 *     "First name",
 *     ValidForm::VFORM_STRING,
 *     array(
 *         // Make this field required
 *         "required" => true,
 *         // It should have a maximum of 10 characters
 *         "maxLength" => 10,
 *         // It should have a minimum of 3 characters
 *         "minLength" => 3
 *     ),
 *     array(
 *         // Error message when required state isn't met
 *         "required" => "This is a required field",
 *         // Error message when input length is larger than 10 characters
 *         "maxLength" => "Maximum of %s characters allowed.",
 *         // Error message when input length is shorter than 3 characters
 *         "minLength" => "Minimum of %s characters required."
 *     ),
 *     array(
 *         // Add a custom class to the input element
 *         // This results in something like
 *         // <input type='text' class='vf__text custom-class'>
 *         "fieldclass" => "custom-class",
 *         // Add a custom class to the field container
 *         // This results in
 *         // <div class='vf__required container-class'>
 *         //     <input type='text'>
 *         // </div>
 *         "class" => "container-class"
 *
 *     )
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
 */
class Text extends Element
{
    /**
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        $strOutput = "";

        $intDynamicCount = $this->getDynamicCount();
        for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
            $strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
        }

        return $strOutput;
    }

    /**
     * @see \ValidFormBuilder\Element::__toHtml()
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
        $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;

        $varValue = $this->__getValue($submitted, $intCount);

        $blnError = ($submitted && ! $this->__validator->validate($intCount) && $blnDisplayErrors) ? true : false;

        if (! $blnSimpleLayout) {
            // *** We asume that all dynamic fields greater than 0 are never required.
            if ($this->__validator->getRequired() && $intCount == 0) {
                $this->setMeta("class", "vf__required");
            } else {
                $this->setMeta("class", "vf__optional");
            }

            if ($this->isRemovable()) {
                $this->setMeta("class", "vf__removable");
            }

            //*** Add data-dynamic="original" or data-dynamic="clone" attributes to dynamic fields
            if ($this->isDynamic()) {
                if ($intCount === 0) {
                    // This is the first, original element. Make sure to define that.
                    $this->setMeta('data-dynamic', 'original', true);
                } else {
                    $this->setMeta('data-dynamic', 'clone', true);
                    $this->setMeta("class", "vf__clone");
                }
            }

            // *** Set custom meta.
            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            if (! $blnLabel) {
                $this->setMeta("class", "vf__nolabel");
            }

            if (! empty($this->__hint) && ($varValue == $this->__hint)) {
                $this->setMeta("class", "vf__hint");
            }

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";

            if ($blnError) {
                $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
            }

            if ($blnLabel) {
                $strLabel = (! empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
                if (! empty($this->__label)) {
                    $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
                }
            }
        } else {
            if (! empty($this->__hint) && ($varValue == $this->__hint)) {
                $this->setMeta("class", "vf__hint");
            }

            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            $this->setMeta("class", "vf__multifielditem");

            if ($this->isRemovable()) {
                $this->setMeta("class", "vf__removable");
            }

            //*** Add data-dynamic="original" or data-dynamic="clone" attributes to dynamic fields
            if ($this->isDynamic()) {
                if ($intCount === 0) {
                    // This is the first, original element. Make sure to define that.
                    $this->setMeta('data-dynamic', 'original', true);
                } else {
                    $this->setMeta('data-dynamic', 'clone', true);
                    $this->setMeta("class", "vf__clone");
                }
            }

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";
        }

        // *** Add max-length attribute to the meta array. This is being read by the getMetaString method.
        if ($this->__validator->getMaxLength() > 0) {
            $this->setFieldMeta("maxlength", $this->__validator->getMaxLength());
        }

        $varValue = htmlspecialchars((string)$varValue, ENT_QUOTES);

        $strOutput .= "<input type=\"text\" value=\"{$varValue}\" name=\"{$strName}\" id=\"{$strId}\"{$this->__getFieldMetaString()} />\n";

        if (! empty($this->__tip)) {
            $this->setTipMeta("class", "vf__tip");

            $strOutput .= "<small{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        if ($this->isRemovable()) {
            $this->setMeta("dynamicRemoveLabelClass", "vf__removeLabel");

            $strOutput .= $this->getRemoveLabelHtml();
        }

        $strOutput .= "</div>\n";

        if (!$blnSimpleLayout && $intCount == $this->getDynamicCount()) {
            $strOutput .= $this->getDynamicHtml();
        }

        return $strOutput;
    }

    /**
     * @see \ValidFormBuilder\Element::toJS()
     */
    public function toJS($intDynamicPosition = false)
    {
        $strOutput = "";
        $strCheck = $this->__sanitizeCheckForJs($this->__validator->getCheck());
        $strRequired = ($this->__validator->getRequired()) ? "true" : "false";
        $intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
        $intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

        $strSanitize = "null";
        if (is_array($this->__validator->getSanitisers())) {
            $strSanitize = json_encode($this->__validator->getSanitisers());
        }

        $strExternalValidation = "null";
        if (is_array($this->__validator->getExternalValidation())) {
            $arrExternalValidation = $this->__validator->getExternalValidation();

            if (isset($arrExternalValidation['javascript'])) {
                $strExternalValidation = json_encode($arrExternalValidation['javascript']);
            }
        }

        $fltMinValue = (is_null($this->__validator->getMinValue())) ? "null" : $this->__validator->getMinValue();
        $fltMaxValue = (is_null($this->__validator->getMaxValue())) ? "null" : $this->__validator->getMaxValue();

        if ($this->__dynamic || $intDynamicPosition) {
            $intDynamicCount = $this->getDynamicCount($intDynamicPosition);
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
                $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;

                // *** We asume that all dynamic fields greater than 0 are never required.
                if ($intDynamicCount > 0) {
                    $strRequired = "false";
                }

                $strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '"
                    . addslashes((string)$this->__validator->getFieldHint()) . "', '"
                    . addslashes((string)$this->__validator->getTypeError()) . "', '"
                    . addslashes((string)$this->__validator->getRequiredError()) . "', '"
                    . addslashes((string)$this->__validator->getHintError()) . "', '"
                    . addslashes((string)$this->__validator->getMinLengthError()) . "', '"
                    . addslashes((string)$this->__validator->getMaxLengthError()) . "', "
                    . $strSanitize . ", "
                    . $strExternalValidation . ", '"
                    . addslashes((string)$this->__validator->getExternalValidationError()) . "', "
                    . $fltMinValue . ", '"
                    . addslashes((string)$this->__validator->getMinValueError()) . "', "
                    . $fltMaxValue . ", '"
                    . addslashes((string)$this->__validator->getMaxValueError()) . "');\n";

                // *** MatchWith logic per dynamic field.
                $strOutput .= $this->matchWithToJs($intCount);

                // *** Render the condition logic per dynamic field.
                $strOutput .= $this->conditionsToJs($intCount);
            }
        } else {
            $strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '"
                . addslashes((string)$this->__validator->getFieldHint()) . "', '"
                . addslashes((string)$this->__validator->getTypeError()) . "', '"
                . addslashes((string)$this->__validator->getRequiredError()) . "', '"
                . addslashes((string)$this->__validator->getHintError()) . "', '"
                . addslashes((string)$this->__validator->getMinLengthError()) . "', '"
                . addslashes((string)$this->__validator->getMaxLengthError()) . "', "
                . $strSanitize . ", "
                . $strExternalValidation . ", '"
                . addslashes((string)$this->__validator->getExternalValidationError()) . "', "
                . $fltMinValue . ", '"
                . addslashes((string)$this->__validator->getMinValueError()) . "', "
                . $fltMaxValue . ", '"
                . addslashes((string)$this->__validator->getMaxValueError()) . "');\n";

            // *** MatchWith logic.
            $strOutput .= $this->matchWithToJs();

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs();
        }

        return $strOutput;
    }
}
