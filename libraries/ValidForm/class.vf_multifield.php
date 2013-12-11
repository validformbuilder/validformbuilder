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

		// Render the field and add it to the multifield field collection.
		$objField = ValidForm::renderField($name, "", $type, $validationRules, $errorHandlers, $meta);

		//*** Set the parent for the new field.
		$objField->setMeta("parent", $this, true);

		$this->__fields->addObject($objField);

		if ($this->__dynamic) {
		    //*** The dynamic count can be influenced by a meta value.
		    $intDynamicCount = (isset($meta["dynamicCount"])) ? $meta["dynamicCount"] : 0;

			$objHiddenField = new VF_Hidden($objField->getId() . "_dynamic", VFORM_INTEGER, array("default" => $intDynamicCount, "dynamicCounter" => true));
			$this->__fields->addObject($objHiddenField);

			$objField->setDynamicCounter($objHiddenField);
		}

		return $objField;
	}

	public function addText($strText, $meta = array()) {
		$objString = new VF_String($strText, $meta);
		$objString->setMeta("parent", $this, true);

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
		//*** Conditional meta should be set before all other meta. Otherwise the set meta is being reset.
		$this->setConditionalMeta();

		// Do nothing if multifield has no child fields.
		if ($this->__fields->count() == 0) {
			return "";
		}

		$blnError = false;
		$arrError = array();

		$strId = "";
		$blnRequired = FALSE;

		foreach ($this->__fields as $field) {
			$objValidator = $field->getValidator();
			if (is_object($objValidator)) {
				//*** Check if this multifield should have required styling.
				if ($objValidator->getRequired()) {
					$blnRequired = TRUE;
				}

				if ($submitted && !$objValidator->validate($intCount) && $blnDisplayError) {
					$blnError = TRUE;

					$strError = $field->getValidator()->getError($intCount);
					if (!in_array($strError, $arrError)) {
						$arrError[] = $strError;
					}
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

		$strId = ($intCount == 0) ? " id=\"{$this->getId()}\"" : " id=\"" . $this->getId() . "_{$intCount}" . "\"";
		$strOutput = "<div{$this->__getMetaString()}{$strId}>\n";

		if ($blnError) $strOutput .= "<p class=\"vf__error\">" . implode("</p><p class=\"vf__error\">", $arrError) . "</p>";

		$strLabel = (!empty($this->__requiredstyle) && $blnRequired) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		if(!empty($this->__label)) $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";

		foreach ($this->__fields as $field) {
			// Skip the hidden dynamic counter fields.
			if (($intCount > 0) && (get_class($field) == "VF_Hidden") && $field->isDynamicCounter()) {
				continue;
			}

			$strOutput .= $field->__toHtml($submitted, true, $blnLabel, $blnDisplayError, $intCount);
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

	public function toJS($blnParentIsDynamic = FALSE) {
		$strOutput = "";

		foreach ($this->__fields as $field) {
			$strOutput .= $field->toJS($this->__dynamic);
		}

		//*** Condition logic.
		if ($this->__dynamic || $blnParentIsDynamic) {
		    $intDynamicCount = $this->getDynamicCount($blnParentIsDynamic);
		    for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
		        //*** Render the condition logic per dynamic field.
		        $strOutput .= $this->conditionsToJs($intCount);
		    }
		} else {
		    //*** Condition logic.
		    $strOutput .= $this->conditionsToJs();
		}

		return $strOutput;
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
			$objCounters = $this->getCountersRecursive($this->getFields());

			foreach ($objCounters as $objCounter) {
			    $intCounterValue = $objCounter->getValidator()->getValue();
			    if ($intCounterValue > $intReturn) {
			        $intReturn = $intCounterValue;
			    }
			}

			if ($intReturn > 0) {
			    // Equalize all counter values inside this area
			    foreach ($objCounters as $objCounter) {
			        $objCounter->setDefault($intReturn);
			    }
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

	public function getId() {
		return $this->getName();
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
			    $objValidator = $objField->getValidator();
			    if (is_object($objValidator)) {
    				$varValue = $objValidator->getValue($intCount);

    				if (!empty($varValue)) {
    					$blnReturn = true;

    					break;
    				}
			    }
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