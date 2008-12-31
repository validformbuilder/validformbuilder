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
 * VF_Element class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
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
	protected $__reservedMeta = array("tip", "hint", "default", "width", "height", "length");

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
	
	public function toHtml($submitted = FALSE) {
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