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

require_once('class.vf_element.php');

/**
 *
 * Select Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.3
 *
 */
class VF_Select extends VF_Element {
	protected $__options;

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$this->__options = new VF_Collection();

		parent::__construct($name, $type, $label, $validationRules, $errorHandlers, $meta);
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		$strOutput = "";

		if ($this->__dynamic) {
			$intDynamicCount = $this->getDynamicCount();
			for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
			}
		} else {
			$strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
		}

		return $strOutput;
	}

	public function __toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0) {
		$strOutput = "";

		$strName 	= ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
		$strId 		= ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;

		if (!$blnSimpleLayout) {
			$blnError = ($submitted && !$this->__validator->validate($intCount) && $blnDisplayErrors) ? TRUE : FALSE;

			//*** We asume that all dynamic fields greater than 0 are never required.
			$strClass = ($this->__validator->getRequired() && $intCount == 0) ? "vf__required" : "vf__optional";

			$strClass = ($blnError) ? $strClass . " vf__error" : $strClass;
			// $strClass = ($this->hasTrigger()) ? $strClass . " vf__targetfield" : $strClass;
			$strClass = (!$blnLabel) ? $strClass . " vf__nolabel" : $strClass;

			$strOutput .= "<div class=\"{$strClass}\">\n";

			if ($blnError) {
				$strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
			}

			$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
			if (!empty($this->__label)) $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
		} else {
			$strOutput = "<div class=\"vf__multifielditem\">\n";
		}

		// if (is_object($this->__targetfield)) {
		// 	$strOutput .= "<div class=\"vf__targetfieldwrap\">";
		// }

		$strOutput .= "<select name=\"{$strName}\" id=\"{$strId}\" {$this->__getMetaString()}>\n";

		if ($this->__options->count() == 0) {
			if (isset($this->__meta["labelRange"]) && is_array($this->__meta["labelRange"])) {
				if (isset($this->__meta["valueRange"]) && is_array($this->__meta["valueRange"]) && count($this->__meta["labelRange"]) == count($this->__meta["valueRange"])) {
					$intIndex = 0;
					foreach ($this->__meta["labelRange"] as $strLabel) {
						$this->addField($strLabel, $this->__meta["valueRange"][$intIndex]);
						$intIndex++;
					}
				} else {
					foreach ($this->__meta["labelRange"] as $strLabel) {
						$this->addField($strLabel, $strLabel);
					}
				}
			} else if (isset($this->__meta["start"]) && is_numeric($this->__meta["start"]) && isset($this->__meta["end"]) && is_numeric($this->__meta["end"])) {
				if ($this->__meta["start"] < $this->__meta["end"]) {
					for ($intIndex = $this->__meta["start"]; $intIndex <= $this->__meta["end"]; $intIndex++) {
						$this->addField($intIndex, $intIndex);
					}
				} else {
					for ($intIndex = $this->__meta["start"]; $intIndex >= $this->__meta["end"]; $intIndex--) {
						$this->addField($intIndex, $intIndex);
					}
				}
			}
		}

		foreach ($this->__options as $option) {
			$strOutput .= $option->toHtml($this->__getValue($submitted, $intCount));
		}

		$strOutput .= "</select>\n";

		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";

		// if (is_object($this->__targetfield)) {
		// 	$strOutput .= $this->__targetfield->toHtml($submitted, $blnSimpleLayout, false, $blnDisplayErrors, $intCount);
		// 	$strOutput .= "</div>\n"; // End of the targetfieldwrap
		// }

		$strOutput .= "</div>\n";

		if (!$blnSimpleLayout && $intCount == $this->getDynamicCount()) {
			$strOutput .= $this->__addDynamicHtml();
		}

		return $strOutput;
	}

	protected function __addDynamicHtml() {
		$strReturn = "";

		if ($this->__dynamic && !empty($this->__dynamicLabel)) {
			$strReturn = "<div class=\"vf__dynamic vf__cf\"><a href=\"#\" data-target-id=\"{$this->__id}\" data-target-name=\"{$this->__name}\">{$this->__dynamicLabel}</a></div>\n";
		}

		return $strReturn;
	}

	public function toJS($blnParentIsDynamic = FALSE) {
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
		$strOutput = "";

		if ($this->__dynamic || $blnParentIsDynamic) {
			$intDynamicCount = $this->getDynamicCount($blnParentIsDynamic);
			for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strId 		= ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
				$strName 	= ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;

				//*** We asume that all dynamic fields greater than 0 are never required.
				if ($intDynamicCount > 0) $strRequired = "false";

				$strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";
			}
		} else {
			$strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";
		}

		// if (is_object($this->__targetfield)) {
		// 	$strOutput .= $this->__targetfield->toJs();
		// }

		return $strOutput;
	}

	public function addField($value, $label, $selected = FALSE) {
		$objOption = new VF_SelectOption($value, $label, $selected);
		$this->__options->addObject($objOption);

		return $objOption;
	}

	// public function addFieldObject($objTarget, $checked = false) {
	// 	// Add checkbox
	// 	$objTrigger = $this->addField($objTarget->getLabel(), $this->getName(true) . "_triggerfield", $checked);

	// 	// Set the defaults on the target element
	// 	$objTarget->setName($this->getName(true) . "_triggerfield");
	// 	$objTarget->setId($this->getRandomId($objTarget->getName()));

	// 	// Set the trigger field.
	// 	$objTarget->setTrigger($objTrigger);

	// 	// This group has a trigger element.
	// 	$this->__targetfield = $objTarget;

	// 	// Add to validator
	// 	$this->__validator->setTargetField($objTarget);

	// 	// $this->__options->addObject($objTarget);
	// }

	public function addGroup($label) {
		$objGroup = new VF_SelectGroup($label);
		$this->__options->addObject($objGroup);

		return $objGroup;
	}

}

?>