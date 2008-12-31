<?php
/**
 * This file is part of ValidFormBuilder.
 *
 * Copyright (c) 2008 Felix Langfeldt
 *
 * ValidFormBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ValidFormBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ValidFormBuilder.  If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * VF_Select class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
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