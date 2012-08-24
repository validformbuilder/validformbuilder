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
require_once('class.vf_validator.php');

/**
 * 
 * FieldValidator Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_FieldValidator extends ClassDynamic {
	protected $__fieldname;
	protected $__type;
	protected $__fieldhint;
	protected $__validvalues = array();
	protected $__minlength;
	protected $__maxlength;
	protected $__matchwith;
	protected $__targetfield;
	protected $__required = FALSE;
	protected $__maxfiles = 1;
	protected $__maxsize = 3000;
	protected $__filetypes;
	protected $__validation;
	protected $__minlengtherror = "The input is too short. The minimum is %s characters.";
	protected $__maxlengtherror = "The input is too long. The maximum is %s characters.";
	protected $__matchwitherror = "The values do not match.";
	protected $__requirederror = "This field is required.";
	protected $__typeerror;
	protected $__overrideerrors = array();
	protected $__maxfileserror = "Too many files selected. The maximum is %s files.";
	protected $__maxsizeerror = "The filesize is too big. The maximum is %s KB.";
	protected $__filetypeerror = "Invalid file types selected. Only types of %s are permitted.";
	protected $__hinterror = "The value is the hint value. Enter your own value.";
	protected $__errors = array();
	
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

	public function getValidValue($intDynamicPosition = 0) {
		$varReturn = null;

		if (isset($this->__validvalues[$intDynamicPosition])) {
			$varReturn = $this->__validvalues[$intDynamicPosition];
		} 

		return $varReturn;
	}
	
	/**
	 * Get the value to validate from either the global request variable or the cached __validvalues array.
	 * @param  integer $intDynamicPosition [Using the intDynamicPosition parameter, you can get the specific value of a dynamic field.]
	 * @return mixed                      [The posted value of the requested field.]
	 */
	public function getValue($intDynamicPosition = 0) {
		if (isset($this->__overrideerrors[$intDynamicPosition]) && empty($this->__overrideerrors[$intDynamicPosition])) {
			$strReturn = NULL;
		} else {

			$strFieldName = ($intDynamicPosition > 0) ? $this->__fieldname . "_" . $intDynamicPosition : $this->__fieldname;
			$varValidValue = (isset($this->__validvalues[$intDynamicPosition])) ? $this->__validvalues[$intDynamicPosition] : null;
			$strReturn = (isset($_REQUEST[$strFieldName])) ? $_REQUEST[$strFieldName] : $varValidValue;
		}
		
		return $strReturn;
	}
	
	/**
	 * The most important function of ValidForm Builder library. This function 
	 * handles all the server-side field validation logic. 
	 * 
	 * @param  integer $intDynamicPosition Using the intDynamicPosition parameter, you can validate a specific dynamic field, if necessary.
	 * @return boolean	                   True if the current field validates, false if not.
	 */
	public function validate($intDynamicPosition = 0) {
		// Reset the internal errors array
		$this->__errors = array();

		//*** Get the value to validate from either the global request variable or the cached __validvalues array.
		$value = $this->getValue($intDynamicPosition);

		//*** Check "required" option.
		if (is_array($value)) {
			$blnEmpty 		= TRUE;
			$strTargetError = "";
			$intCount 		= 0;

			foreach ($value as $valueItem) {
			
				// Check if empty
				if (is_object($this->__targetfield)) {
			
					if ($valueItem == $this->__targetfield->getName()) {
			
						// Validate target field and set error/validvalue
						if ($this->__targetfield->getValidator()->validate($intDynamicPosition)) {
							$valueItem = $this->__targetfield->getValidator()->getValidValue($intDynamicPosition);

						} else {
							$valueItem 		= "";
							$strTargetError = $this->__targetfield->getValidator()->getError($intDynamicPosition);
							break;
						}
					}
				}

				if (!empty($valueItem)) {
					$blnEmpty = FALSE;
					break;
				}

				$intCount++;
			}

			if ($blnEmpty) {
				if ($this->__required || !empty($strTargetError)) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = (!empty($strTargetError)) ? $strTargetError : $this->__requirederror;
				} else {
					$this->__validvalues[$intDynamicPosition] = "";
					return TRUE;
				}
			}
		} else {
			$blnTargetError = false;
			if (is_object($this->__targetfield)) {
						echo "cool";
				if ($value == $this->__targetfield->getName()) {
					// Validate target field and set error/validvalue
					if ($this->__targetfield->getValidator()->validate($intDynamicPosition)) {
						$value = $this->__targetfield->getValidator()->getValidValue($intDynamicPosition);
					} else {
						$blnTargetError = true;
						$this->__errors[$intDynamicPosition] = $this->__targetfield->getValidator()->getError($intDynamicPosition);
					}

					// return $blnReturn;
				}
			}

			if (empty($value) && !$blnTargetError) {
				if ($this->__required) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = $this->__requirederror;
				} else {
					unset($this->__validvalues[$intDynamicPosition]);
					
					if (empty($this->__matchwith)) return TRUE;
				}
			}
		}

		//** Overwrite 'fieldname value' in triggerfield with it's targetfield's value
		if (is_array($value)) {
			$intCount = 0;
			foreach ($value as $strValue) {
				if (is_object($this->__targetfield)) {
					if ($this->__targetfield->getName() == $strValue) {

						if ($this->__targetfield->getValidator()->validate($intDynamicPosition)) {
							$value[$intCount] = $this->__targetfield->getValidator()->getValidValue($intDynamicPosition);
						} else {
							unset($this->__validvalues[$intDynamicPosition]);
							$this->__errors[$intDynamicPosition] = $this->__targetfield->getValidator()->getError($intDynamicPosition);
						}
					}
				}

				$intCount++;
			}
		}

		//*** Check if value is hint value.
		if (!$this->__hasError($intDynamicPosition)) {
			if (!empty($this->__fieldhint) && !is_array($value)) {
				if ($this->__fieldhint == $value) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = $this->__hinterror;
				}
			}
		}
						
		//*** Check minimum input length.
		if (!$this->__hasError($intDynamicPosition)) {
			if ($this->__minlength > 0	&& is_array($value)) {
				if (count($value) < $this->__minlength) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
				}
			} else if ($this->__minlength > 0
					&& strlen($value) < $this->__minlength) {
				unset($this->__validvalues[$intDynamicPosition]);
				$this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
			}
		}

		//*** Check maximum input length.
		if (!$this->__hasError($intDynamicPosition)) {
			if ($this->__maxlength > 0	&& is_array($value)) {
				if (count($value) > $this->__maxlength) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = sprintf($this->__maxlengtherror, $this->__maxlength);
				}
			} else if ($this->__maxlength > 0
					&& strlen($value) > $this->__maxlength) {
				unset($this->__validvalues[$intDynamicPosition]);
				$this->__errors[$intDynamicPosition] = sprintf($this->__maxlengtherror, $this->__maxlength);
			}
		}
		
		//*** Check matching values.
		if (!$this->__hasError($intDynamicPosition)) {
			if (!empty($this->__matchwith)) {
				$matchValue = $this->__matchwith->getValue();
				if (empty($matchValue)) $matchValue = NULL;
				if (empty($value)) $value = NULL;
				
				if ($matchValue !== $value) {
					unset($this->__validvalues[$intDynamicPosition]);
					$this->__errors[$intDynamicPosition] = $this->__matchwitherror;
				} else if (is_null($value)) {
					return TRUE;
				}
			}
		}
		
		//*** Check specific types.
		if (!$this->__hasError($intDynamicPosition)) {
			switch ($this->__type) {
				case VFORM_CUSTOM:
				case VFORM_CUSTOM_TEXT:
					$blnValidType = VF_Validator::validate($this->__validation, $value);
					break;
				default:
					$blnValidType = VF_Validator::validate($this->__type, ($this->__type == VFORM_CAPTCHA) ? $this->__fieldname : $value);
			}

			if (!$blnValidType) {
				unset($this->__validvalues[$intDynamicPosition]);
				$this->__errors[$intDynamicPosition] = $this->__typeerror;
			} else {
				$this->__validvalues[$intDynamicPosition] = $value;
			}
		}
		
		//*** Override error.
		if (isset($this->__overrideerrors[$intDynamicPosition]) && !empty($this->__overrideerrors[$intDynamicPosition])) {
			unset($this->__validvalues[$intDynamicPosition]);
			$this->__errors[$intDynamicPosition] = $this->__overrideerrors[$intDynamicPosition];
		}
		
		return (!isset($this->__validvalues[$intDynamicPosition])) ? false : true;
	}
	
	public function setError($strError, $intDynamicPosition = 0) {
		$this->__overrideerrors[$intDynamicPosition] = $strError;
	}

	public function getError($intDynamicPosition = 0) {
		return (isset($this->__errors[$intDynamicPosition]) && !empty($this->__errors[$intDynamicPosition])) ? $this->__errors[$intDynamicPosition] : "";
	}
	
	public function getCheck() {
		$strReturn = "";
	
		switch ($this->__type) {
			case VFORM_CUSTOM:
			case VFORM_CUSTOM_TEXT:
				$strReturn = $this->__validation;
				break;
			default:
				$strReturn = VF_Validator::getCheck($this->__type);
		}
		
		return $strReturn;
	}

	private function __hasError($intDynamicPosition = 0) {
		return (isset($this->__errors[$intDynamicPosition]) && !empty($this->__errors[$intDynamicPosition]));
	}
	
}

?>