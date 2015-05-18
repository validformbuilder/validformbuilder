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
 * Password Class
 *
 * Create a password fields. Password fields can utilize a special validation rule called `matchWith`
 * By using the `matchWith` validation, you can require that two fields (for instance 'password' and 'repeat password')
 * have the exact same value. As with everything in ValidForm Builder, this is validated both client-side and
 * server-side.
 *
 * #### Example; Match two password fields
 * ```php
 * $objNewPassword = $objForm->addField(
 *     "new-password",
 *     "New Password",
 *     ValidForm::VFORM_PASSWORD
 * );
 * $objForm->addField(
 *     "repeat-password",
 *     "Repeat Password",
 *     ValidForm::VFORM_PASSWORD,
 *     array(
 *         // Link the fieldobject to match with
 *         "matchWith" => $objNewPassword
 *     ),
 *     array(
 *         // Set an error message if the fields don't match when submitted
 *         "matchWith" => "Password fields do not match"
 *     )
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class Password extends Element
{

    /**
     * @internal
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        $strOutput = "";

        if ($this->__dynamic) {
            $intDynamicCount = $this->getDynamicCount();
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
            }
        } else {
            $strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
        }

        return $strOutput;
    }

    /**
     * @internal
     * @see \ValidFormBuilder\Element::__toHtml()
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        $strOutput = "";

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

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}\">\n";
        }

        // *** Add maxlength attribute to the meta array. This is being read by the getMetaString method.
        if ($this->__validator->getMaxLength() > 0) {
            $this->setFieldMeta("maxlength", $this->__validator->getMaxLength());
        }

        $varValue = htmlspecialchars($varValue, ENT_QUOTES);

        $strOutput .= "<input type=\"password\" value=\"{$varValue}\" name=\"{$strName}\" id=\"{$strId}\"{$this->__getFieldMetaString()} autocomplete=\"off\" />\n";

        if (! empty($this->__tip)) {
            $strOutput .= "<small class=\"vf__tip\"{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        $strOutput .= "</div>\n";

        if (! $blnSimpleLayout && $this->__dynamic && ! empty($this->__dynamicLabel) && ($intCount == $this->getDynamicCount())) {
            $strOutput .= "<div class=\"vf__dynamic vf__cf\"><a href=\"#\" data-target-id=\"{$this->__id}\" data-target-name=\"{$this->__name}\">{$this->__dynamicLabel}</a></div>\n";
        }

        return $strOutput;
    }

    /**
     * @internal
     * @see \ValidFormBuilder\Element::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strCheck = $this->__validator->getCheck();
        $strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
        $strRequired = ($this->__validator->getRequired()) ? "true" : "false";
        ;
        $intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
        $intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

        if ($this->__dynamic || $intDynamicPosition) {
            $intDynamicCount = $this->getDynamicCount($intDynamicPosition);
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
                $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;

                // *** We asume that all dynamic fields greater than 0 are never required.
                if ($intDynamicCount > 0) {
                    $strRequired = "false";
                }

                $strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

                // *** Render the matchWith logic per dynamic field.
                $strOutput .= $this->matchWithToJs($intCount);

                // *** Render the condition logic per dynamic field.
                $strOutput .= $this->conditionsToJs($intCount);
            }
        } else {
            $strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

            // *** MatchWith logic.
            $strOutput .= $this->matchWithToJs();

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs();
        }

        return $strOutput;
    }
}
