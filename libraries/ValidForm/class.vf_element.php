<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@neverwoods.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package    ValidForm
 * @author     Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright  2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://validformbuilder.org
 ***************************/

require_once('class.vf_base.php');

/**
 *
 * Element Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 */
class VF_Element extends VF_Base {
	protected $__name;
	protected $__label;
	protected $__tip = null;
	protected $__type;
	protected $__hint = null;
	protected $__default = null;
	protected $__dynamic = null;
	protected $__dynamiccounter = false;
	protected $__dynamicLabel = null;
	protected $__requiredstyle;
	protected $__validator;

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		// Set meta class
		$this->setClass($type, $meta);

		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__label = $label;
		$this->__type = $type;
		$this->__meta = $meta;

		//*** Set label & field specific meta
		$this->__initializeMeta();

		$this->__parent = $this->getMeta("parent", null);
		$this->__tip = $this->getMeta("tip", $this->__tip);
		$this->__hint = $this->getMeta("hint", $this->__hint);
		$this->__default = $this->getMeta("default", $this->__default);
		$this->__dynamic = $this->getMeta("dynamic", $this->__dynamic);
		$this->__dynamicLabel = $this->getMeta("dynamicLabel", $this->__dynamicLabel);
		$this->__dynamiccounter = (!is_null($this->getMeta("dynamicCounter", null))) ? true : $this->__dynamiccounter;

		// $this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
		$this->__validator = new VF_FieldValidator($this, $validationRules, $errorHandlers);
	}

	public function isDynamicCounter() {
		return false;
	}

	protected function setClass($type, &$meta) {
		switch ($type) {
			case VFORM_STRING:
				$this->setFieldMeta("class", "vf__string");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_WORD:
				$this->setFieldMeta("class", "vf__word");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_EMAIL:
				$this->setFieldMeta("class", "vf__email");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_URL:
			case VFORM_SIMPLEURL:
				$this->setFieldMeta("class", "vf__url");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_CUSTOM:
				$this->setFieldMeta("class", "vf__custom");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_CURRENCY:
				$this->setFieldMeta("class", "vf__currency");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_DATE:
				$this->setFieldMeta("class", "vf__date");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_NUMERIC:
				$this->setFieldMeta("class", "vf__numeric");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_INTEGER:
				$this->setFieldMeta("class", "vf__integer");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_PASSWORD:
				$this->setFieldMeta("class", "vf__password");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_CAPTCHA:
				$this->setFieldMeta("class", "vf__text_small");
				break;
			case VFORM_HTML:
				$this->setFieldMeta("class", "vf__html");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_CUSTOM_TEXT:
				$this->setFieldMeta("class", "vf__custom");
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_TEXT:
				$this->setFieldMeta("class", "vf__text");
				break;
			case VFORM_FILE:
				$this->setFieldMeta("class", "vf__file");
				break;
			case VFORM_BOOLEAN:
				$this->setFieldMeta("class", "vf__checkbox");
				break;
			case VFORM_RADIO_LIST:
			case VFORM_CHECK_LIST:
				$this->setFieldMeta("class", "vf__list");
				break;
			case VFORM_SELECT_LIST:
				if (!isset($meta["multiple"])) {
					$this->setFieldMeta("class", "vf__one");
				} else {
					$this->setFieldMeta("class", "vf__multiple");
				}

				$this->setFieldMeta("class", "vf__select");
				break;
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
		return "alert('Field type of field {$this->__name} not defined.');\n";
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
	 * Get default value
	 * @return Ambigous <array, string>
	 */
	public function getDefault()
	{
	    return $this->__default;
	}

	public function setDefault($varValue) {
		$this->__default = $varValue;
	}

	/**
	 * Get the value of the field. If the value is *valid* then it will return that value, otherwise the invalid value is returned.
	 *
	 * @param boolean $submitted Indicate if the form is submitted.
	 * @param integer $intDynamicPosition The position of the field in a dynamic field setup.
	 * @return Ambigous <NULL, string>
	 */
	public function __getValue($submitted = FALSE, $intDynamicPosition = 0) {
		$varReturn = NULL;

		if ($submitted) {
			if ($this->__validator->validate($intDynamicPosition)) {
				$varReturn = $this->__validator->getValidValue($intDynamicPosition);
			} else {
				$varReturn = $this->__validator->getValue($intDynamicPosition);
			}
		} else {
		    if (is_array($this->__default)) {
    			if (isset($this->__default[$intDynamicPosition]) && strlen($this->__default[$intDynamicPosition]) > 0) {
    				$varReturn = $this->__default[$intDynamicPosition];
    			}
		    } else {
    			if (strlen($this->__default) > 0) {
    				$varReturn = $this->__default;
    			} else if (strlen($this->__hint) > 0) {
    				$varReturn = $this->__hint;
    			}
		    }
		}

		if (!$varReturn && ((get_class($this) == "VF_Hidden") && $this->isDynamicCounter())) {
			$varReturn = (int)0;
		}

		return $varReturn;
	}

}

?>