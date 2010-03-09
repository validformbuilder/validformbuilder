<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Area class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.2.0
 */
  
require_once('class.classdynamic.php');

class VF_Area extends ClassDynamic {
	protected $__label;
	protected $__active;
	protected $__name;
	protected $__checked;
	protected $__meta;
	protected $__form;
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
		$objField = $this->__form->addField($name, $label, $type, $validationRules, $errorHandlers, $meta, TRUE);
				
		array_push($this->__fields, $objField);
		
		return $objField;
	}
	
	public function addMultiField($label = NULL, $meta = array()) {
		$objField = new VF_MultiField($label, $meta);
		
		$objField->setForm($this->__form);
		$objField->setRequiredStyle($this->__requiredstyle);
		
		array_push($this->__fields, $objField);
		
		return $objField;
	}
	
	public function toHtml($submitted = FALSE) {
		$value = ValidForm::get($this->__name);
		$strChecked = ($this->__active && $this->__checked && empty($value) && !$submitted) ? " checked=\"checked\"" : "";
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
	
	public function getId() {
		return null;
	}
	
	public function getType() {
		return 0;
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