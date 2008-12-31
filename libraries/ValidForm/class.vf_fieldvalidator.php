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
 * VF_FieldValidator class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
 
require_once('class.classdynamic.php');
require_once('class.vf_validator.php');

class VF_FieldValidator extends ClassDynamic {
	protected $__fieldname;
	protected $__type;
	protected $__fieldhint;
	protected $__validvalue;
	protected $__minlength;
	protected $__maxlength;
	protected $__required = FALSE;
	protected $__maxfiles = 1;
	protected $__maxsize = 3000;
	protected $__filetypes;
	protected $__validation;
	protected $__minlengtherror = "The input is too short. The minimum is %s characters.";
	protected $__maxlengtherror = "The input is too long. The maximum is %s characters.";
	protected $__requirederror = "This field is required.";
	protected $__typeerror;
	protected $__maxfileserror = "Too many files selected. The maximum is %s files.";
	protected $__maxsizeerror = "The filesize is too big. The maximum is %s KB.";
	protected $__filetypeerror = "Invalid file types selected. Only types of %s are permitted.";
	protected $__hinterror = "The value is the hint value. Enter your own value.";
	protected $__error;
	
	public function __construct($fieldName, $fieldType, $validationRules, $errorHandlers, $fieldHint = NULL) {
		foreach ($validationRules as $key => $value) {
			$property = strtolower("__" . $key);
			if (property_exists($this, $property)) {
				$this->$property = $value;
			}
		}
		
		foreach ($errorHandlers as $key => $value) {
			$property = strtolower("__" . $key . "error");
			if (property_exists($this, $property)) {
				$this->$property = $value;
			}
		}
		
		$this->__fieldname = str_replace("[]", "", $fieldName);
		$this->__type = $fieldType;
		$this->__fieldhint = $fieldHint;
	}
	
	public function getValue() {
		$strReturn = (array_key_exists($this->__fieldname, $_REQUEST)) ? $_REQUEST[$this->__fieldname] : NULL;
		
		return $strReturn;
	}
	
	public function validate() {
		$value = $this->getValue();
		
		//*** Check "required" option.
		if (is_array($value)) {
			$blnEmpty = TRUE;
			foreach ($value as $valueItem) {
				if (!empty($valueItem)) {
					$blnEmpty = FALSE;
					break;
				}
			}
			
			if ($blnEmpty) {
				if ($this->__required) {
					$this->__validvalue = NULL;
					$this->__error = $this->__requirederror;
				} else {
					$this->__validvalue = "";
					return TRUE;
				}
			}
		} else {
			if (empty($value)) {
				if ($this->__required) {
					$this->__validvalue = NULL;
					$this->__error = $this->__requirederror;
				} else {
					$this->__validvalue = "";
					return TRUE;
				}
			}
		}

		//*** Check if value is hint value.
		if (empty($this->__error)) {
			if (!empty($this->__fieldhint) && !is_array($value)) {
				if ($this->__fieldhint == $value) {
					$this->__validvalue = NULL;
					$this->__error = $this->__hinterror;
				}
			}
		}
						
		//*** Check minimum input length.
		if (empty($this->__error)) {
			if ($this->__minlength > 0	&& is_array($value)) {
				if (count($value) < $this->__minlength) {
					$this->__validvalue = NULL;
					$this->__error = sprintf($this->__minlengtherror, $this->__minlength);
				}
			} else if ($this->__minlength > 0
					&& strlen($value) < $this->__minlength) {
					echo 'sdfsdfsdf';
				$this->__validvalue = NULL;
				$this->__error = sprintf($this->__minlengtherror, $this->__minlength);
			}
		}

		//*** Check maximum input length.
		if (empty($this->__error)) {
			if ($this->__maxlength > 0	&& is_array($value)) {
				if (count($value) > $this->__maxlength) {
					$this->__validvalue = NULL;
					$this->__error = sprintf($this->__maxlengtherror, $this->__maxlength);
				}
			} else if ($this->__maxlength > 0
					&& strlen($value) > $this->__maxlength) {
				$this->__validvalue = NULL;
				$this->__error = sprintf($this->__maxlengtherror, $this->__maxlength);
			}
		}
		
		//*** Check specific types.
		if (empty($this->__error)) {
			switch ($this->__type) {
				case VFORM_CUSTOM:
					$blnValidType = VF_Validator::validate($this->__validation, $value);
					break;
				default:
					$blnValidType = VF_Validator::validate($this->__type, ($this->__type == VFORM_CAPTCHA) ? $this->__fieldname : $value);
			}

			if (!$blnValidType) {
				$this->__validvalue = NULL;
				$this->__error = $this->__typeerror;
			} else {
				$this->__validvalue = $value;
			}
		}
		
		return (is_null($this->__validvalue)) ? FALSE : TRUE;
	}
	
	public function getCheck() {
		$strReturn = "";
	
		switch ($this->__type) {
			case VFORM_CUSTOM:
				$strReturn = $this->__validation;
				break;
			default:
				$strReturn = VF_Validator::getCheck($this->__type);
		}
		
		return $strReturn;
	}
	
}

?>