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
 * Element Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.2.2
 *
 */
class VF_Element extends ClassDynamic {
	protected $__id;
	protected $__name;
	protected $__label;
	protected $__tip;
	protected $__type;
	protected $__meta;
	protected $__labelmeta;
	protected $__hint;
	protected $__default;
	protected $__dynamic;
	protected $__dynamicLabel;
	protected $__requiredstyle;
	protected $__validator;
	protected $__reservedMeta = array("tip", "hint", "default", "width", "height", "length", "start", "end", "path", "labelStyle", "labelClass", "labelRange", "valueRange", "dynamic", "dynamicLabel");

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		if (is_null($validationRules)) $validationRules = array();
		if (is_null($errorHandlers)) $errorHandlers = array();
		if (is_null($meta)) $meta = array();
		
		$labelMeta = (isset($meta['labelStyle'])) ? array("style" => $meta['labelStyle']) : array();
		if (isset($meta['labelClass'])) $labelMeta["class"] = $meta['labelClass'];
		
		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__label = $label;
		$this->__type = $type;
		$this->__meta = $meta;
		$this->__labelmeta = $labelMeta;
		$this->__tip = (array_key_exists("tip", $meta)) ? $meta["tip"] : NULL;
		$this->__hint = (array_key_exists("hint", $meta)) ? $meta["hint"] : NULL;
		$this->__default = (array_key_exists("default", $meta)) ? $meta["default"] : NULL;
		$this->__dynamic = (array_key_exists("dynamic", $meta)) ? $meta["dynamic"] : NULL;
		$this->__dynamicLabel = (array_key_exists("dynamicLabel", $meta)) ? $meta["dynamicLabel"] : NULL;
		
		$this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);		
	}
	
	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE) {
		return "Field type not defined.";
	}
	
	public function setError($strError) {
		//*** Override the validator message.
		$this->__validator->setError($strError);
	}
	
	public function toJS() {
		return "alert('Field type not defined.');\n";
	}
	
	public function getRandomId($name) {
		$strReturn = $name;
		
		if (strpos($name, "[]") !== FALSE) {
			$strReturn = str_replace("[]", "_" . rand(100000, 900000), $name);
		} else {
			$strReturn = $name . "_" . rand(100000, 900000);
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return $this->__validator->validate();
	}
	
	public function isDynamic() {
		return ($this->__dynamic) ? true : false;
	}
	
	public function getDynamicCount() {
		return ValidForm::get($this->getName() . "_dynamic", 0);
	}
	
	public function getValue($intDynamicPosition = 0) {
		$varValue = NULL;
		
		if ($intDynamicPosition > 0) {
			$objValidator = $this->__validator;
			$objValidator->validate($intDynamicPosition);
			
			$varValue = $objValidator->getValidValue();
		} else {
			$varValue = $this->__validator->getValidValue();
		}
		
		return $varValue;
	}
	
	public function hasFields() {
		return FALSE;
	}
	
	protected function __getValue($submitted = FALSE) {
		$strReturn = NULL;
		
		if ($submitted) {
			if ($this->__validator->validate()) {
				$strReturn = $this->__validator->getValidValue();
			} else {
				$strReturn = $this->__validator->getValue();
			}		
		} else {
			if (!empty($this->__default)) {
				$strReturn = $this->__default;
			} else if (!empty($this->__hint)) {
				$strReturn = $this->__hint;
			}
		}
		
		return $strReturn;
	}
	
	protected function __getMetaString() {
		$strOutput = "";
		
		foreach ($this->__meta as $key => $value) {
			if (!in_array($key, $this->__reservedMeta)) {
				$strOutput .= " {$key}=\"{$value}\"";
			}
		}
		
		return $strOutput;
	}
	
	protected function __getLabelMetaString() {
		$strOutput = "";
		
		if (is_array($this->__labelmeta)) {
			foreach ($this->__labelmeta as $key => $value) {
				if (!in_array($key, $this->__reservedMeta)) {
					$strOutput .= " {$key}=\"{$value}\"";
				}
			}
		}
				
		return $strOutput;
	}

}

?>