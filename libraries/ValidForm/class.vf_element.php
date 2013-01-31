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
	protected $__tip = null;
	protected $__type;
	protected $__meta;
	protected $__labelmeta;
	protected $__hint = null;
	protected $__default = null;
	protected $__dynamic = null;
	protected $__dynamiccounter = false;
	protected $__dynamicLabel = null;
	protected $__requiredstyle;
	protected $__validator;

	protected $__reservedmeta = array("data", "dynamicCounter", "tip", "hint", "default", "width", "height", "length", "start", "end", "path", "labelStyle", "labelClass", "labelRange", "valueRange", "dynamic", "dynamicLabel", "matchWith");

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		if (is_null($validationRules)) $validationRules = array();
		if (is_null($errorHandlers)) $errorHandlers = array();
		if (is_null($meta)) $meta = array();

		// Set meta class
		$this->setClass($type, $meta);

		$labelMeta = (isset($meta['labelStyle'])) ? array("style" => $meta['labelStyle']) : array();
		if (isset($meta['labelClass'])) $labelMeta["class"] = $meta['labelClass'];

		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__label = $label;
		$this->__type = $type;
		$this->__meta = $meta;
		$this->__labelmeta = $labelMeta;
		$this->__tip = (array_key_exists("tip", $meta)) ? $meta["tip"] : $this->__tip;
		$this->__hint = (array_key_exists("hint", $meta)) ? $meta["hint"] : $this->__hint;
		$this->__default = (array_key_exists("default", $meta)) ? $meta["default"] : $this->__default;
		$this->__dynamic = (array_key_exists("dynamic", $meta)) ? $meta["dynamic"] : $this->__dynamic;
		$this->__dynamicLabel = (array_key_exists("dynamicLabel", $meta)) ? $meta["dynamicLabel"] : $this->__dynamicLabel;
		$this->__dynamiccounter = (array_key_exists("dynamicCounter", $meta)) ? true : $this->__dynamiccounter;

		$this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
	}

	protected function setClass($type, &$meta) {
		$strClass = "";
		switch ($type) {
			case VFORM_STRING:
			case VFORM_WORD:
			case VFORM_EMAIL:
			case VFORM_URL:
			case VFORM_SIMPLEURL:
			case VFORM_CUSTOM:
			case VFORM_CURRENCY:
			case VFORM_DATE:
			case VFORM_NUMERIC:
			case VFORM_INTEGER:
			case VFORM_PASSWORD:
				$meta["class"] = (!isset($meta["class"])) ? "vf__text" : $meta["class"] . " vf__text";
				break;
			case VFORM_CAPTCHA:
				$meta["class"] = (!isset($meta["class"])) ? "vf__text_small" : $meta["class"] . " vf__text_small";
				break;
			case VFORM_HTML:
			case VFORM_CUSTOM_TEXT:
			case VFORM_TEXT:
				$meta["class"] = (!isset($meta["class"])) ? "vf__text" : $meta["class"] . " vf__text";
				break;
			case VFORM_FILE:
				$meta["class"] = (!isset($meta["class"])) ? "vf__file" : $meta["class"] . " vf__file";
				break;
			case VFORM_BOOLEAN:
				$meta["class"] = (!isset($meta["class"])) ? "vf__checkbox" : $meta["class"] . " vf__checkbox";
				break;
			case VFORM_RADIO_LIST:
			case VFORM_CHECK_LIST:
				$meta["class"] = (!isset($meta["class"])) ? "vf__radiobutton" : $meta["class"] . " vf__radiobutton";
				break;
			case VFORM_SELECT_LIST:
				if (!isset($meta["class"])) {
					if (!isset($meta["multiple"])) {
						$meta["class"] = "vf__one";
					} else {
						$meta["class"] = "vf__multiple";
					}
				} else {
					if (!isset($meta["multiple"])) {
						$meta["class"] .= " vf__one";
					} else {
						$meta["class"] .= " vf__multiple";
					}
				}
				break;
		}

		if (!empty($strClass)) {
			$meta["class"] = (isset($meta["class"])) ? $meta["class"] .= " " . $strClass : $strClass;
		}
	}

	/**
	 * Check if the current fields contains a condition object
	 * @param  String  $strType Condition type (e.g. 'required', 'disabled', 'visible' etc.)
	 * @return boolean          True if element has condition object set, false if not
	 */
	public function hasCondition($strType) {
		return $this->__validator->hasCondition($strType);
	}

	/**
	 * Add a new condition to the current field
	 * @param [type] $strType           [description]
	 * @param [type] $blnValue          [description]
	 * @param [type] $arrComparisons    [description]
	 * @param [type] $intComparisonType [description]
	 */
	public function addCondition($strType, $blnValue, $arrComparisons, $intComparisonType = VFORM_MATCH_ANY) {
		$this->__validator->addCondition($this, $strType, $blnValue, $arrComparisons, $intComparisonType = VFORM_MATCH_ANY);
	}

	public function getCondition($strType) {
		$objConditions = $this->__validator->getConditions();

		foreach ($objConditions as $objCondition) {
			if ($objCondition->getType() === strtolower($strType)) {
				$blnReturn = true;
				break;
			}
		}
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		return "Field type not defined.";
	}

	public function __toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0) {
		return $this->toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
	}

	public function setError($strError, $intDynamicPosition = 0) {
		//*** Override the validator message.
		$this->__validator->setError($strError, $intDynamicPosition);
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

	/**
	 * Validate the current field. This is a wrapper method to call the FieldValidator->validate() method.
	 * @return boolean 	True if field validates, false if not.
	 */
	public function isValid($intCount = null) {
		$blnReturn = false;
		$intDynamicCount = $this->getDynamicCount();

		if (is_null($intCount)) {
			// No specific dynamic count is set, loop through dynamic fields internally
			for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$blnReturn = $this->__validator->validate($intCount);

				if (!$blnReturn) {
					break;
				}
			}
		} else {
			// Validate just one, we're looping through the external fields externally
			$blnReturn = $this->__validator->validate($intCount);
		}

		return $blnReturn;
	}

	/**
	 * Check if the current field is a dynamic field.
	 * @return boolean True if dynamic, false if not.
	 */
	public function isDynamic() {
		return $this->__dynamic;
	}

	/**
	 * Get the number of dynamic fields from the dynamic counter field.
	 * @return [type] [description]
	 */
	public function getDynamicCount($blnParentIsDynamic = FALSE) {
		$intReturn = 0;

		if (($this->__dynamic || $blnParentIsDynamic) && is_object($this->__dynamiccounter)) {
			$intReturn = $this->__dynamiccounter->getValidator()->getValue();
		}

		return (int)$intReturn;
	}

	public function setDynamicCounter(&$objCounter) {
		$this->__dynamiccounter = $objCounter;
	}

	/**
	 * Get the *valid* value of the current field.
	 * @param  integer $intDynamicPosition 	Optional parameter to get the value of a dynamic field.
	 * @return mixed                      	The valid value of this field. If validation fails, it returns null.
	 */
	public function getValue($intDynamicPosition = 0) {
		$varValue = NULL;

		if ($intDynamicPosition > 0) {
			$objValidator = $this->__validator;
			$objValidator->validate($intDynamicPosition);

			$varValue = $objValidator->getValidValue($intDynamicPosition);
		} else {
			$varValue = $this->__validator->getValidValue();
		}

		return $varValue;
	}

	/**
	 * Placeholder function to determine wheter or not a field contains other fields.
	 * @return boolean Return false by default.
	 */
	public function hasFields() {
		return FALSE;
	}

	/**
	 * DEPRECATED METHOD
	 */
	public function addTriggerJs() {
		return "objForm.addTrigger(\"deprecated\", \"deprecated\");\n";
	}

	/**
	 * Link a field to this element. If the trigger field is selected / checked, this element will become enabled.
	 * @param vf_element $objField ValidForm Builder field element
	 */
	// public function setTrigger($objField) {
	// 	$this->__triggerfield = $objField;
	// }

	/**
	 * Check if this element has a triggerfield.
	 * @return boolean True if a triggerfield is set, false if not.
	 */
	// public function hasTrigger() {
	// 	return is_object($this->__triggerfield);
	// }

	/**
	 * If an element's name is updated, also update the name in it's corresponding validator.
	 * Therefore, we cannot use the default 'magic method' getName()
	 * @param string $strName The new name
	 */
	public function setName($strName) {
		parent::setName($strName);

		if (is_object($this->__validator)) {
			$this->__validator->setFieldName($strName);
		}
	}

	/**
	 * Store data in the current object. This data will not be visibile in any output
	 * and will only be used for internal purposes. For example, you can store some custom
	 * data from your CMS or an other library in a field object, for later use.
	 * Note: Using this method will overwrite any previously set data with the same key!
	 *
	 * @param [string] 	$strKey   	The key for this storage
	 * @param [mixed] 	$varValue 	The value to store
	 * @return	[boolean] 			True if set successful, false if not.
	 */
	public function setData($strKey = null, $varValue = null) {
		$varReturn = false;
		$this->__meta["data"] = (isset($this->__meta["data"])) ? $this->__meta["data"] : array();

		if (isset($this->__meta["data"])) {
			if (!is_null($strKey) && !is_null($varValue)) {
				$this->__meta["data"][$strKey] = $varValue;
			}
		}

		return isset($this->__meta["data"][$strKey]);
	}

	/**
	 * Get a value from the internal data array.
	 *
	 * @param  [string] $key The key of the data attribute to return
	 * @return [mixed]       If a key is provided, return it's value. If no key
	 *                       provided, return the whole data array. If anything
	 *                       is not set or incorrect, return false.
	 */
	public function getData($key = null) {
		$varReturn = false;

		if (isset($this->__meta["data"])) {
			if ($key == null) {
				$varReturn = $this->__meta["data"];
			} else {
				if (isset($this->__meta["data"][$key])) {
					$varReturn = $this->__meta["data"][$key];
				}
			}
		}

		return $varReturn;
	}

	/**
	 * Get the value of the field. If the value is *valid* then it will return that value, otherwise the invalid value is returned.
	 *
	 * @param boolean $submitted Indicate if the form is submitted.
	 * @param integer $intDynamicPosition The position of the field in a dynamic field setup.
	 * @return Ambigous <NULL, string>
	 */
	protected function __getValue($submitted = FALSE, $intDynamicPosition = 0) {
		$varReturn = NULL;

		if ($submitted) {
			if ($this->__validator->validate($intDynamicPosition)) {
				$varReturn = $this->__validator->getValidValue($intDynamicPosition);
			} else {
				$varReturn = $this->__validator->getValue($intDynamicPosition);
			}
		} else {
			if (!empty($this->__default)) {
				$varReturn = $this->__default;
			} else if (!empty($this->__hint)) {
				$varReturn = $this->__hint;
			}
		}

		if(!$varReturn && ((get_class($this) == "VF_Hidden") && $this->isDynamicCounter())) {
			$varReturn = (int)0;
		}

		return $varReturn;
	}

	protected function __getMetaString() {
		$strOutput = "";

		foreach ($this->__meta as $key => $value) {
			if (!in_array($key, $this->__reservedmeta)) {
				$strOutput .= " {$key}=\"{$value}\"";
			}
		}

		return $strOutput;
	}

	protected function __getLabelMetaString() {
		$strOutput = "";

		if (is_array($this->__labelmeta)) {
			foreach ($this->__labelmeta as $key => $value) {
				if (!in_array($key, $this->__reservedmeta)) {
					$strOutput .= " {$key}=\"{$value}\"";
				}
			}
		}

		return $strOutput;
	}

}

?>