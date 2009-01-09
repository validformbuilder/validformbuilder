<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Select class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.0
 */
  
require_once('class.vf_element.php');

class VF_Select extends VF_Element {
	protected $__options = array();

	public function toHtml($submitted = FALSE) {
		$blnError = ($submitted && !$this->__validator->validate()) ? TRUE : FALSE;
		
		$strClass = ($this->__validator->getRequired()) ? "vf__required" : "vf__optional";
		$strClass = ($blnError) ? $strClass . " vf__error" : $strClass;		
		$strOutput = "<div class=\"{$strClass}\">\n";
		
		if ($blnError) $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError()}</p>";
				
		$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		$strOutput .= "<label for=\"{$this->__id}\">{$strLabel}</label>\n";
		$strOutput .= "<select name=\"{$this->__name}\" id=\"{$this->__id}\" {$this->__getMetaString()}>\n";
		
		foreach ($this->__options as $option) {
			$strOutput .= $option->toHtml($this->__getValue($submitted));
		}
		
		$strOutput .= "</select>\n";
		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";
		$strOutput .= "</div>\n";
		
		return $strOutput;
	}
	
	public function toJS() {
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : $strCheck;
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";;
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
		$strMaxLengthError = sprintf($this->__validator->getMaxLengthError(), $intMaxLength);
		$strMinLengthError = sprintf($this->__validator->getMinLengthError(), $intMinLength);
		
		return "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '{$this->__validator->getFieldHint()}', '{$this->__validator->getTypeError()}', '{$this->__validator->getRequiredError()}', '{$this->__validator->getHintError()}', '{$strMinLengthError}', '{$strMaxLengthError}');\n";
	}
	
	public function addField($value, $label, $selected = FALSE) {
		$objOption = new VF_SelectOption($value, $label, $selected);
		array_push($this->__options, $objOption);
		
		return $objOption;
	}
	
}

?>