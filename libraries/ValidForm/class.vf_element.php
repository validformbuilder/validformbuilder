<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Element class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.1
 */
 
require_once('class.classdynamic.php');
 
class VF_Element extends ClassDynamic {
	protected $__id;
	protected $__name;
	protected $__label;
	protected $__tip;
	protected $__type;
	protected $__meta;
	protected $__hint;
	protected $__default;
	protected $__requiredstyle;
	protected $__validator;
	protected $__reservedMeta = array("tip", "hint", "default", "width", "height", "length", "start", "end", "path");

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		if (is_null($validationRules)) $validationRules = array();
		if (is_null($errorHandlers)) $errorHandlers = array();
		if (is_null($meta)) $meta = array();
		
		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__label = $label;
		$this->__type = $type;
		$this->__meta = $meta;
		$this->__tip = (array_key_exists("tip", $meta)) ? $meta["tip"] : NULL;
		$this->__hint = (array_key_exists("hint", $meta)) ? $meta["hint"] : NULL;
		$this->__default = (array_key_exists("default", $meta)) ? $meta["default"] : NULL;
		
		$this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);		
	}
	
	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE) {
		return "Field type not defined.";
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
	
	public function getValue() {
		return $this->__validator->getValidValue();
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

}

?>