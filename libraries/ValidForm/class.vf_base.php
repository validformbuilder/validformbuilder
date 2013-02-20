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

/**
 * ValidForm Base class.
 * All ValidForm classes share this base logic.
 */
class VF_Base extends ClassDynamic {
	protected $__id;
	protected $__name;
	protected $__parent;
	protected $__conditions = array();
	protected $__meta = array();
	protected $__fieldmeta = array();
	protected $__labelmeta = array();
	protected $__magicmeta = array("label", "field");
	protected $__reservedfieldmeta = array("multiple");
	protected $__reservedlabelmeta = array();
	protected $__reservedmeta = array(
		"parent",
		"data",
		"dynamicCounter",
		"tip",
		"hint",
		"default",
		"width",
		"height",
		"length",
		"start",
		"end",
		"path",
		"labelStyle",
		"labelClass",
		"labelRange",
		"valueRange",
		"dynamic",
		"dynamicLabel",
		"matchWith"
	);

	/**
	 * Add a new condition to the current field
	 * @param [type] $strType           [description]
	 * @param [type] $blnValue          [description]
	 * @param [type] $arrComparisons    [description]
	 * @param [type] $intComparisonType [description]
	 */
	public function addCondition($strType, $blnValue, $arrComparisons, $intComparisonType = VFORM_MATCH_ANY) {
		if ($this->hasCondition($strType)) {
			// Get an existing condition if it's already there.
			$objCondition = $this->getCondition($strType);
		} else {
			// Add a new one if this condition type doesn't exist yet.
			$objCondition = new VF_Condition($this, $strType, $blnValue, $intComparisonType);
		}

		if (is_array($arrComparisons) && count($arrComparisons) > 0) {
			/* @var $varComparison Array|VF_Comparison */
			foreach ($arrComparisons as $varComparison) {
				if (is_array($varComparison) || get_class($varComparison) === "VF_Comparison") {
					try {
						$objCondition->addComparison($varComparison);
					} catch (InvalidArgumentException $e) {
						throw new Exception("Could not set condition: " . $e->getMessage(), 1);
					}
				} else {
					throw new InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
				}
			}

			array_push($this->__conditions, $objCondition);
		} else {
			throw new InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
		}
	}

	/**
	 * Get element's VF_Condition object
	 * Note: When chaining methods, always use hasCondition() first before chaining
	 * for example 'getCondition()->isMet()'.
	 *
	 * @param  String $strType 		Condition type e.g. 'required', 'visibile' and 'disabled'
	 * @return VF_Condition|null    The found condition or null if no condition is found.
	 */
	public function getCondition($strProperty) {
		$objReturn = null;

		$objConditions = $this->getConditions();
		foreach ($objConditions as $objCondition) {
			if ($objCondition->getProperty() === strtolower($strProperty)) {
				$objReturn = $objCondition;
				break;
			}
		}

		if (is_null($objReturn) && is_object($this->__parent)) {
			//*** Find condition in parent.
			$objReturn = $this->__parent->getCondition($strProperty);
		}

		return $objReturn;
	}

	public function getMetCondition($strProperty) {
		$objReturn = null;

		$objConditions = $this->getConditions();
		foreach ($objConditions as $objCondition) {
			if ($objCondition->getProperty() === strtolower($strProperty) && $objCondition->isMet()) {
				$objReturn = $objCondition;
				break;
			}
		}

		if (is_null($objReturn) && is_object($this->__parent)) {
			//*** Find condition in parent.
			$objReturn = $this->__parent->getMetCondition($strProperty);
		}

		return $objReturn;
	}


	/**
	 * Check if the current fields contains a condition object
	 * @param  String  $strProperty Condition type (e.g. 'required', 'disabled', 'visible' etc.)
	 * @return boolean          True if element has condition object set, false if not
	 */
	public function hasCondition($strProperty) {
		$blnReturn = false;

		foreach ($this->__conditions as $objCondition) {
			if ($objCondition->getProperty() === strtolower($strProperty)) {
				$blnReturn = true;
				break;
			}
		}

		return $blnReturn;
	}

	public function hasConditions() {
		return (count($this->__conditions) > 0);
	}

	public function setConditionalMeta() {

		foreach ($this->__conditions as $objCondition) {
			$blnResult = $objCondition->isMet();

			switch ($objCondition->getProperty()) {
				case "visible":
					// This can be applied on all sorts of subjects.
					if ($blnResult) {
						if ($objCondition->getValue()) {
							$this->setMeta("style", "display: block;");
						} else {
							$this->setMeta("style", "display: none;");
						}
					} else {
						if ($objCondition->getValue()) {
							$this->setMeta("style", "display: none;");
						} else {
							$this->setMeta("style", "display: block;");
						}
					}

				case "required":
					// This can only be applied on all subjects except for Paragraphs
					if (get_class($objCondition->getSubject()) !== "VF_Paragraph") {

						if ($blnResult) {
							if ($objCondition->getValue()) {
								$this->setMeta("class", "vf__required", true);
							} else {
								$this->setMeta("class", "vf__optional", true);
							}
						} else {
							if ($objCondition->getValue()) {
								$this->setMeta("class", "vf__optional", true);
							} else {
								$this->setMeta("class", "vf__required", true);
							}
						}
					}
					break;

				case "enabled":
					// This can only be applied on all subjects except for Paragraphs
					if (get_class($objCondition->getSubject()) !== "VF_Paragraph") {

						if ($blnResult) {
							if ($objCondition->getValue()) {
								$this->setFieldMeta("disabled", "", true);
							} else {
								$this->setFieldMeta("disabled", "disabled", true);
							}
						} else {
							if ($objCondition->getValue()) {
								$this->setFieldMeta("disabled", "disabled", true);
							} else {
								$this->setFieldMeta("disabled", "", true);
							}
						}
					}
					break;
			}
		}
	}

	/**
	 * Set meta property.
	 * @param string  	$property     Property name.
	 * @param mixed  	$value        Property value.
	 * @param boolean 	$blnOverwrite Overwrite previous property value.
	 */
	public function setMeta($property, $value, $blnOverwrite = false) {
		return $this->__setMeta($property, $value, $blnOverwrite);
	}

	public function setFieldMeta($property, $value, $blnOverwrite = false) {
		return $this->__setMeta("field" . $property, $value, $blnOverwrite);
	}

	public function setLabelMeta($property, $value, $blnOverwrite = false) {
		return $this->__setMeta("label" . $property, $value, $blnOverwrite);
	}

	/**
	 * Get meta property.
	 * @param  string $property Property to get from internal meta array.
	 * @return string           Property value or empty string of none is set.
	 */
	public function getMeta($property = null, $fallbackValue = "") {
		if (is_null($property)) {
			return $this->__meta;
		} else {
			return (isset($this->__meta[$property]) && !is_null($this->__meta[$property])) ? $this->__meta[$property] : $fallbackValue;
		}
	}

	/**
	 * Get field meta property.
	 * @param  string $property Property to get from internal field meta array.
	 * @return string           Property value or empty string of none is set.
	 */
	public function getFieldMeta($property = null, $fallbackValue = "") {
		if (is_null($property)) {
			return $this->__fieldmeta;
		} else {
			return (isset($this->__fieldmeta[$property]) && !is_null($this->__fieldmeta[$property])) ? $this->__fieldmeta[$property] : $fallbackValue;
		}
	}

	/**
	 * Get label meta property.
	 * @param  string $property Property to get from internal label meta array.
	 * @return string           Property value or empty string of none is set.
	 */
	public function getLabelMeta($property = null, $fallbackValue = "") {
		if (is_null($property)) {
			return $this->__labelmeta;
		} else {
			return (isset($this->__labelmeta[$property]) && !is_null($this->__labelmeta[$property])) ? $this->__labelmeta[$property] : $fallbackValue;
		}
	}

	public function getName() {
		$strName = parent::getName();
		if (empty($strName)) {
			$strName = $this->__name = $this->__generateName();
		}

		return $strName;
	}

	public function toJS() {
		$strOutput = "";

		if ($this->hasConditions() && (count($this->getConditions() > 0))) {
			foreach ($this->getConditions() as $objCondition) {
				$strOutput .= "objForm.addCondition(" . json_encode($objCondition->jsonSerialize()) . ");\n";
			}
		}

		return $strOutput;
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
		$arrData = $this->getMeta("data", array());

		if (!is_null($strKey) && !is_null($varValue)) {
			$arrData[$strKey] = $varValue;
		}

		// Set and overwrite previous value.
		$this->setMeta("data", $arrData, true);

		// Return boolean value
		return !!$this->getData($key);
	}

	/**
	 * Get a value from the internal data array.
	 *
	 * @param  [string] $key The key of the data attribute to return
	 * @return [mixed]
	 */
	public function getData($key = null) {
		$varReturn 	= false;
		$arrData 	= $this->getMeta("data", null);

		if (!is_null($arrData)) {
			if ($key == null) {
				$varReturn = $arrData;
			} else {
				if (isset($arrData[$key])) {
					$varReturn = $arrData[$key];
				}
			}
		}

		return $varReturn;
	}

	protected function __generateName() {
		return strtolower(get_class($this)) . "_" . mt_rand();
	}

	protected function __getMetaString() {
		$strOutput = "";

		foreach ($this->__meta as $key => $value) {
			if (!in_array($key, array_merge($this->__reservedmeta, $this->__fieldmeta))) {
				$strOutput .= " {$key}=\"{$value}\"";
			}
		}

		return $strOutput;
	}

	protected function __getFieldMetaString() {
		$strOutput = "";

		if (is_array($this->__fieldmeta)) {
			foreach ($this->__fieldmeta as $key => $value) {
				if (!in_array($key, $this->__reservedmeta)) {
					$strOutput .= " {$key}=\"{$value}\"";
				}
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

	/**
	 * Filter out special field or label specific meta tags from the main
	 * meta array and add them to the designated meta arrays __fieldmeta or __labelmeta.
	 * Example: $meta["labelstyle"] = "width: 20px"; will become $__fieldmeta["style"] = "width: 20px;";
	 * Any meta key that starts with 'label' or 'field' will be assigned to it's
	 * corresponding internal meta array.
	 *
	 * @return
	 */
	protected function __initializeMeta() {
		foreach ($this->__meta as $key => $value) {
			if (in_array($key, $this->__reservedfieldmeta)) {
				$key = "field" . $key;
			}

			if (in_array($key, $this->__reservedlabelmeta)) {
				$key = "label" . $key;
			}

			$strMagicKey = strtolower(substr($key, 0, 5));
			if (in_array($strMagicKey, $this->__magicmeta)) {
				$strMethod = "set" . ucfirst($strMagicKey) . "Meta";
				$this->$strMethod(strtolower(substr($key, -(strlen($key) - 5))), $value);

				unset($this->__meta[$key]);
			}
		}
	}

	protected function __setMeta($property, $value, $blnOverwrite = false) {
		$internalMetaArray = &$this->__meta;

		//*** Re-set internalMetaArray if property has magic key 'label' or 'field'
		$strMagicKey = strtolower(substr($property, 0, 5));
		if (in_array($strMagicKey, $this->__magicmeta)) {
			switch ($strMagicKey) {
				case "field":
					$internalMetaArray = &$this->__fieldmeta;
					$property = strtolower(substr($property, -(strlen($property) - 5)));
					break;
				case "label":
					$internalMetaArray = &$this->__labelmeta;
					$property = strtolower(substr($property, -(strlen($property) - 5)));
					break;
				default:
			}
		}

		if ($blnOverwrite) {
			if (empty($value) || is_null($value)) {
				unset($internalMetaArray[$property]);
			} else {
				$internalMetaArray[$property] = $value;
			}

			return $value;
		} else {
			$varMeta = (isset($internalMetaArray[$property])) ? $internalMetaArray[$property] : "";

			//*** Define delimiter per meta property.
			switch ($property) {
				case "style":
					$strDelimiter = ";";
					break;

				default:
					$strDelimiter = " ";
			}

			//*** Add the value to the property string.
			$arrMeta = explode($strDelimiter, $varMeta);
			$arrMeta[] = $value;

			// Make sure no empty values are left in the array.
			$arrMeta = array_filter($arrMeta);
			$varMeta = implode($strDelimiter, $arrMeta);

			$internalMetaArray[$property] = $varMeta;

			return $varMeta;
		}
	}

	protected function __replaceMeta($property, $originalValue, $replacement = null) {
		$internalMetaArray = &$this->__meta;

		//*** Re-set internalMetaArray if property has magic key 'label' or 'field'
		$strMagicKey = strtolower(substr($property, 0, 5));
		if (in_array($strMagicKey, $this->__magicmeta)) {
			switch ($strMagicKey) {
				case "field":
					$internalMetaArray = &$this->__fieldmeta;
					$property = strtolower(substr($property, -(strlen($property) - 5)));
					break;
				case "label":
					$internalMetaArray = &$this->__labelmeta;
					$property = strtolower(substr($property, -(strlen($property) - 5)));
					break;
				default:
			}
		}

		foreach ($internalMetaArray as $prop => $value) {
			if ($property == $prop) {
				$varMeta = (isset($internalMetaArray[$property])) ? $internalMetaArray[$property] : "";

				//*** Define delimiter per meta property.
				switch ($property) {
					case "style":
						$strDelimiter = ";";
						break;

					default:
						$strDelimiter = " ";
				}

				//*** Add the value to the property string.
				$arrMeta = explode($strDelimiter, $varMeta);
			}
		}
	}
}
?>