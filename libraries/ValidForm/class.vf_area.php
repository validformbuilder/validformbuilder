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
 * VF_Area class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.2
 */
  
require_once('class.classdynamic.php');

class VF_Area extends ClassDynamic {
	protected $__label;
	protected $__active;
	protected $__name;
	protected $__checked;
	protected $__meta;
	protected $__requiredstyle;
	protected $__fields = array();
	
	public function __construct($label, $active = FALSE, $name = NULL, $checked = FALSE, $meta = array()) {
		$this->__label = $label;
		$this->__active = $active;
		$this->__name = $name;
		$this->__checked = $checked;
		$this->__meta = $meta;
	}
	
	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		switch ($type) {
			case VFORM_STRING:
			case VFORM_WORD:
			case VFORM_EMAIL:
			case VFORM_SIMPLEURL:
			case VFORM_CUSTOM:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				
				$objField = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_PASSWORD:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				
				$objField = new VF_Password($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_CAPTCHA:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text_small";
				
				$objField = new VF_Captcha($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_CURRENCY:
			case VFORM_DATE:
			case VFORM_NUMERIC:
			case VFORM_INTEGER:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text_small";
				
				$objField = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_TEXT:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				if (!array_key_exists("rows", $meta)) $meta["rows"] = "5";
				if (!array_key_exists("cols", $meta)) $meta["cols"] = "21";
				
				$objField = new VF_Textarea($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_FILE:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__file";
				
				$objField = new VF_File($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_BOOLEAN:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__checkbox";
				
				$objField = new VF_Checkbox($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_RADIO_LIST:
			case VFORM_CHECK_LIST:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__radiobutton";
				
				$objField = new VF_Group($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_SELECT_LIST:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__one";
				if (array_key_exists("multiple", $meta)) $meta["class"] = "vf__multiple";
				
				$objField = new VF_Select($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			default:
				$objField = new VF_Element($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
		}
		
		array_push($this->__fields, $objField);
		
		$objField->setRequiredStyle($this->__requiredstyle);
		
		return $objField;
	}
	
	public function toHtml($submitted = FALSE) {
		$value = ValidForm::get($this->__name);
		$strChecked = ($this->__active && $this->__checked && is_null($value) && !$submitted) ? " checked=\"checked\"" : "";
		$strChecked = ($this->__active && !empty($value)) ? " checked=\"checked\"" : $strChecked;
		
		$strClass = (array_key_exists("class", $this->__meta)) ? $this->__meta["class"] : "";
		$strClass = ($this->__active && empty($strChecked)) ? $strClass . " vf__disabled" : $strClass;
		
		$strOutput = "<fieldset class=\"vf__area {$strClass}\">\n";
		if ($this->__active) {
			$label = "<label for=\"{$this->__name}\"><input type=\"checkbox\" name=\"{$this->__name}\" id=\"{$this->__name}\" {$strChecked} /> {$this->__label}</label>";
		} else {
			$label = $this->__label;
		}
		if (!empty($this->__label)) $strOutput .= "<legend>{$label}</legend>\n";
				
		foreach ($this->__fields as $field) {
			$submitted = ($this->__active && empty($value)) ? FALSE : $submitted;
			$strOutput .= $field->toHtml($submitted);
		}
		
		$strOutput .= "</fieldset>\n";
	
		return $strOutput;
	}
	
	public function toJS() {
		$strReturn = "";
		
		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS();
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return $this->__validate();
	}
	
	public function getFields() {
		return $this->__fields;
	}
	
	public function getValue() {
		$value = ValidForm::get($this->__name);
		return (($this->__active && !empty($value)) || !$this->__active) ? TRUE : FALSE;
	}
	
	public function hasFields() {
		return (count($this->__fields) > 0) ? TRUE : FALSE;
	}
	
	private function __validate() {
		$value = ValidForm::get($this->__name);
		$blnReturn = TRUE;
		
		if ($this->__active && empty($value)) {
			//*** Not active;
		} else {
			foreach ($this->fields as $field) {
				if (!$field->isValid()) {
					$blnReturn = FALSE;
					break;
				}
			}
		}
		
		return $blnReturn;
	}
	
}

?>