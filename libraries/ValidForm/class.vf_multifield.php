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

require_once('class.vf_base.php');

/**
 *
 * MultiField Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_MultiField extends VF_Base {
	protected $__label;
	protected $__dynamic;
	protected $__dynamicLabel;
	protected $__requiredstyle;
	protected $__fields;

	public function __construct($label, $meta = array()) {
		$this->__label = $label;
		$this->__meta = $meta;

		//*** Set label & field specific meta
		$this->__initializeMeta();

		$this->__fields = new VF_Collection();

		$this->__dynamic = $this->getMeta("dynamic", $this->__dynamic);
		$this->__dynamicLabel = $this->getMeta("dynamicLabel", $this->__dynamicLabel);
	}

	public function addField($name, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		// Creating dynamic fields inside a multifield is not supported.
		if (array_key_exists("dynamic", $meta)) unset($meta["dynamic"]);
		if (array_key_exists("dynamicLabel", $meta)) unset($meta["dynamicLabel"]);

		//*** Set the parent for the new field.
		$meta["parent"] = $this;

		// Render the field and add it to the multifield field collection.
		$objField = ValidForm::renderField($name, "", $type, $validationRules, $errorHandlers, $meta);

		$this->__fields->addObject($objField);

		if ($this->__dynamic) {
			$objHiddenField = new VF_Hidden($objField->getId() . "_dynamic", VFORM_INTEGER, array("default" => "0", "dynamicCounter" => true));
			$this->__fields->addObject($objHiddenField);

			$objField->setDynamicCounter($objHiddenField);
		}

		return $objField;
	}

	public function addText($strText, $meta = array()) {
		$objString = new VF_String($strText, $meta);
		$this->__fields->addObject($objString);

		return $objString;
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true) {
		$strOutput = "";

		if ($this->__dynamic) {
			$intDynamicCount = $this->getDynamicCount();
			for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayError, $intCount);
			}
		} else {
			$strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayError);
		}

		return $strOutput;
	}

	public function __toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true, $intCount = 0) {
		$blnError = ($submitted && !$this->__validate($intCount));

		//*** Check if this multifield should have required styling.
		$strId = "";
		$blnRequired = FALSE;
		foreach ($this->__fields as $field) {
			if (empty($strId)) {
				$strId = ($intCount == 0) ? $field->id : $field->id . "_" . $intCount;
			}

			$objValidator = $field->getValidator();
			if (is_object($objValidator)) {
				if ($objValidator->getRequired()) {
					$blnRequired = TRUE;
				}
			}
		}

		//*** We asume that all dynamic fields greater than 0 are never required.
		if ($blnRequired && $intCount == 0) {
			$this->setMeta("class", "vf__required");
		} else {
			$this->setMeta("class", "vf__optional");
		}

		//*** Set custom meta.
		if ($blnError) $this->setMeta("class", "vf__error");
		$this->setMeta("class", "vf__multifield vf__cf");

		$this->setConditionalMeta();
		$strOutput 	= "<div{$this->__getMetaString()}>\n";

		$strLabel = (!empty($this->__requiredstyle) && $blnRequired) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		if(!empty($this->__label)) $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";

		$arrFields = array();
		foreach ($this->__fields as $field) {
			// Skip the hidden dynamic counter fields.
			if (($intCount > 0) && (get_class($field) == "VF_Hidden") && $field->isDynamicCounter()) {
				continue;
			}

			$strOutput .= $field->__toHtml($submitted, true, $blnLabel, $blnDisplayError, $intCount);

			$arrFields[$field->getId()] = $field->getName();
		}

		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";
		$strOutput .= "</div>\n";

		if ($intCount == $this->getDynamicCount()) {
			$strOutput .= $this->__addDynamicHtml();
		}

		return $strOutput;
	}

	protected function __addDynamicHtml() {
		$strReturn = "";

		if ($this->__dynamic && !empty($this->__dynamicLabel)) {
			$arrFields = array();
			// Generate an array of field id's
			foreach ($this->__fields as $field) {
				// Skip the hidden dynamic counter fields.
				if ((get_class($field) == "VF_Hidden") && $field->isDynamicCounter()) {
					continue;
				}
				$arrFields[$field->getId()] = $field->getName();
			}

			$strReturn .= "<div class=\"vf__dynamic vf__cf\">";
			$strReturn .= "<a href=\"#\" data-target-id=\"" . implode("|", array_keys($arrFields)) . "\" data-target-name=\"" . implode("|", array_values($arrFields)) . "\">{$this->__dynamicLabel}</a>";
			$strReturn .= "</div>";
		}

		return $strReturn;
	}

	public function toJS() {
		$strReturn = "";

		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS($this->__dynamic);
		}

		if ($this->hasConditions() && (count($this->getConditions() > 0))) {
			foreach ($this->getConditions() as $objCondition) {
				$strOutput .= "objForm.addCondition(" . json_encode($objCondition->jsonSerialize()) . ");\n";
			}
		}

		return $strReturn;
	}

	public function isValid() {
		$intDynamicCount = $this->getDynamicCount();

		for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
			$blnReturn = $this->__validate($intCount);

			if (!$blnReturn) {
				break;
			}
		}

		return $blnReturn;
	}

	public function isDynamic() {
		return ($this->__dynamic) ? true : false;
	}

	public function getDynamicCount() {
		$intReturn = 0;

		if ($this->__dynamic) {
			$objSubFields = $this->getFields();
			$objSubField = ($objSubFields->count() > 0) ? $objSubFields->getFirst() : NULL;

			if (is_object($objSubField)) {
				$intReturn = $objSubField->getDynamicCounter()->getValidator()->getValue();
			}
		}

		return $intReturn;
	}

	public function getFields() {
		return $this->__fields;
	}

	public function getValue() {
		return TRUE;
	}

	public function getName() {
		return null;
	}

	public function getId() {
		return null;
	}

	public function getType() {
		return 0;
	}

	/**
	 * Loop through all child fields and check their values. If one value is not empty,
	 * the MultiField has content.
	 *
	 * @param  integer $intCount The current dynamic count.
	 * @return boolean           True if multifield has content, false if not.
	 */
	public function hasContent($intCount = 0) {
		$blnReturn = false;

		foreach ($this->__fields as $objField) {
			if (get_class($objField) !== "VF_Hidden") {
				$varValue = $objField->getValidator()->getValue($intCount);

				if (!empty($varValue)) {
					$blnReturn = true;
				}

				break;
			}
		}

		return $blnReturn;
	}

	public function hasFields() {
		return ($this->__fields->count() > 0) ? TRUE : FALSE;
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

	private function __validate($intCount = null) {
		$blnReturn = TRUE;
		foreach ($this->__fields as $field) {
			if (!$field->isValid($intCount)) {
				$blnReturn = FALSE;
				break;
			}
		}

		return $blnReturn;
	}

}

?>