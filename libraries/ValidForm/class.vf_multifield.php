<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/
  
require_once('class.classdynamic.php');

/**
 * 
 * MultiField Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.1
 *
 */
class VF_MultiField extends ClassDynamic {
	protected $__label;
	protected $__meta;
	protected $__form;
	protected $__dynamic;
	protected $__dynamicLabel;
	protected $__requiredstyle;
	protected $__fields = array();
	
	public function __construct($label, $meta = array()) {
		$this->__label = $label;
		$this->__meta = $meta;
		
		$this->__dynamic = (array_key_exists("dynamic", $meta)) ? $meta["dynamic"] : NULL;
		$this->__dynamicLabel = (array_key_exists("dynamicLabel", $meta)) ? $meta["dynamicLabel"] : NULL;
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
		if(!empty($this->__label) $strOutput .= "<label for=\"{$strId}\">{$strLabel}</label>\n";
		
		$arrFields = array();
		foreach ($this->__fields as $field) {
			$strOutput .= $field->toHtml($submitted, TRUE);
			
			$arrFields[$field->getId()] = $field->getName();
		}
		
		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";
		$strOutput .= "</div>\n";
		
		if ($this->__dynamic && !empty($this->__dynamicLabel)) {
			$strOutput .= "<div class=\"vf__dynamic\"><a href=\"#\" data-target-id=\"" . implode("|", array_keys($arrFields)) . "\" data-target-name=\"" . implode("|", array_values($arrFields)) . "\">{$this->__dynamicLabel}</a>";
			
			foreach ($arrFields as $key => $value) {
				$strOutput .= "<input type=\"hidden\" id=\"{$key}_dynamic\" name=\"{$value}_dynamic\" value=\"0\" />";
			}
			
			$strOutput .= "</div>";
		}
		
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
	
	public function isDynamic() {
		return ($this->__dynamic) ? true : false;
	}
	
	public function getDynamicCount() {
		$intReturn = 0;
		
		$objSubFields = $this->getFields();
		$objSubField = (count($objSubFields) > 0) ? current($objSubFields) : NULL;
		
		if (is_object($objSubField)) {
			$intReturn = $objSubField->getDynamicCount();
		}
		
		return $intReturn;
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