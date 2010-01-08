<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_MultiField class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.2.0
 */
  
require_once('class.classdynamic.php');

class VF_MultiField extends ClassDynamic {
	protected $__label;
	protected $__meta;
	protected $__form;
	protected $__requiredstyle;
	protected $__fields = array();
	
	public function __construct($label, $meta = array()) {
		$this->__label = $label;
		$this->__meta = $meta;
	}
	
	public function addField($name, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$objField = $this->__form->addField($name, "", $type, $validationRules, $errorHandlers, $meta, TRUE);
		
		array_push($this->__fields, $objField);
		
		return $objField;
	}
	
	public function toHtml($submitted = FALSE) {
		$blnRequired = FALSE;
		$blnError = FALSE;
		$strError = "";
		$strId = "";
		
		foreach ($this->__fields as $field) {
			if (empty($strId)) {
				$strId = $field->id;
			}
			
			if ($field->validator->getRequired()) {
				$blnRequired = TRUE;
			}
			
			if ($submitted && !$field->validator->validate()) {
				$blnError = TRUE;
				$strError .= "<p class=\"vf__error\">{$field->validator->getError()}</p>";
			}
		}
		
		$strClass = ($blnRequired) ? "vf__required" : "vf__optional";
		$strClass = (array_key_exists("class", $this->__meta)) ? $strClass . " " . $this->__meta["class"] : $strClass;
		$strClass = ($blnError) ? $strClass . " vf__error" : $strClass;
		$strOutput = "<div class=\"vf__multifield {$strClass}\">\n";
		
		if ($blnError) $strOutput .= $strError;
				
		$strLabel = (!empty($this->__requiredstyle) && $blnRequired) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		$strOutput .= "<label for=\"{$strId}\">{$strLabel}</label>\n";
		
		foreach ($this->__fields as $field) {
			$strOutput .= $field->toHtml($submitted, TRUE);
		}
		
		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";
		$strOutput .= "</div>\n";
		
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
		return TRUE;
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
		$blnReturn = TRUE;
		
		foreach ($this->__fields as $field) {
			if (!$field->isValid()) {
				$blnReturn = FALSE;
				break;
			}
		}
		
		return $blnReturn;
	}
	
}

?>