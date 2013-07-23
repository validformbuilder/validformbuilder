<?php
/***************************
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
 * @package    ValidForm
 * @author     Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright  2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://validformbuilder.org
 ***************************/

require_once('class.vf_element.php');

/**
 *
 * Checkbox Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.4
 *
 */
class VF_Checkbox extends VF_Element {

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		$blnError = ($submitted && !$this->__validator->validate() && $blnDisplayErrors) ? TRUE : FALSE;

		if (!$blnSimpleLayout) {
			//*** We asume that all dynamic fields greater than 0 are never required.
		    if ($this->__validator->getRequired()) {
		        $this->setMeta("class", "vf__required");
		    } else {
		        $this->setMeta("class", "vf__optional");
		    }
		
		    if ($blnError) $this->setMeta("class", "vf__error");
		    if (!$blnLabel) $this->setMeta("class", "vf__nolabel");
		
		    // Call this right before __getMetaString();
		    $this->setConditionalMeta();
		
		    $strOutput = "<div{$this->__getMetaString()}>\n";
		
		    if ($blnError) $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError()}</p>";
		
		    if ($this->__getValue($submitted)) {
		        //*** Add the "checked" attribute to the input field.
		        $this->setFieldMeta("checked", "checked");
		    } else {
		        //*** Remove the "checked" attribute from the input field. Just to be sure it wasn't set before.
		        $this->setFieldMeta("checked", null, TRUE);
		    }
		
		    if ($blnLabel) {
		        $strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		        if (!empty($this->__label)) $strOutput .= "<label for=\"{$this->__id}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
		    }
		} else {
		    if ($blnError) $this->setMeta("class", "vf__error");
		    $this->setMeta("class", "vf__multifielditem");

		    // Call this right before __getMetaString();
		    $this->setConditionalMeta();

		    $strOutput = "<div{$this->__getMetaString()}>\n";

		    if ($this->__getValue($submitted)) {
		        //*** Add the "checked" attribute to the input field.
		        $this->setFieldMeta("checked", "checked");
		    } else {
		        //*** Remove the "checked" attribute from the input field. Just to be sure it wasn't set before.
		        $this->setFieldMeta("checked", null, TRUE);
		    }
		}

		$strOutput .= "<input type=\"checkbox\" name=\"{$this->__name}\" id=\"{$this->__id}\"{$this->__getFieldMetaString()}/>\n";

		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";

		$strOutput .= "</div>\n";

		return $strOutput;
	}

	public function toJS() {
		$strOutput = "";

		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";;
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

		$strOutput .= "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

		//*** Condition logic.
		$strOutput .= $this->conditionsToJs();

		return $strOutput;
	}

	public function getValue($intDynamicPosition = 0) {
		$varValue = parent::getValue($intDynamicPosition);
		return (strlen($varValue) > 0 && $varValue !== 0) ? TRUE : FALSE;
	}

	public function getDefault($intDynamicPosition = 0) {
		return (strlen($this->__default) > 0 && $this->getValue($intDynamicPosition)) ? "on" : null;
	}

}

?>