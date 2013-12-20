<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
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

require_once('class.vf_element.php');

/**
 * Group Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 */
class VF_Group extends VF_Element {
	protected $__fields;

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$this->__fields = new VF_Collection();

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
		$strOutput 	= "";

		$blnError = ($submitted && !$this->__validator->validate($intCount) && $blnDisplayErrors) ? TRUE : FALSE;

		if (!$blnSimpleLayout) {
			//*** We asume that all dynamic fields greater than 0 are never required.
			if ($this->__validator->getRequired()) {
				$this->setMeta("class", "vf__required");
			} else {
				$this->setMeta("class", "vf__optional");
			}

			//*** Set custom meta.
			if ($blnError) $this->setMeta("class", "vf__error");
			if (!$blnLabel) $this->setMeta("class", "vf__nolabel");

			// Call this right before __getMetaString();
			$this->setConditionalMeta();

			$strOutput = "<div{$this->__getMetaString()}>\n";

			if ($blnError) {
				$strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
			}

			if ($blnLabel) {
				$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
				if (!empty($this->__label)) $strOutput .= "<label{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
			}
		} else {
			if ($blnError) $this->setMeta("class", "vf__error");
			$this->setMeta("class", "vf__multifielditem");

			// Call this right before __getMetaString();
			$this->setConditionalMeta();

			$strOutput = "<div{$this->__getMetaString()}\">\n";
		}

		$strOutput .= "<fieldset{$this->__getFieldMetaString()}>\n";

		foreach ($this->__fields as $objField) {
			switch (get_class($objField)) {
				case "VF_GroupField":
					$strOutput .= $objField->toHtml($this->__getValue($submitted, $intCount), $submitted, $intCount);

					break;
			}
		}

		$strOutput .= "</fieldset>\n";

		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";

		$strOutput .= "</div>\n";

		return $strOutput;
	}

	public function toJS($blnParentIsDynamic = false) {
		$strOutput = "";
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

		if ($this->__dynamic || $blnParentIsDynamic) {
			$intDynamicCount = $this->getDynamicCount($blnParentIsDynamic);
			for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strId 		= ($intCount == 0) ? $this->getId() : $this->getId() . "_" . $intCount;
				$strName 	= ($intCount == 0) ? $this->getName() : $this->getName() . "_" . $intCount;

				//*** We asume that all dynamic fields greater than 0 are never required.
				if ($intDynamicCount > 0) $strRequired = "false";

				$strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

				//*** Render the condition logic per dynamic field.
				$strOutput .= $this->conditionsToJs($intCount);
			}
		} else {
			$strOutput = "objForm.addElement('{$this->getId()}', '{$this->getName()}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

			//*** Condition logic.
			$strOutput .= $this->conditionsToJs();
		}

		return $strOutput;
	}

	public function getId() {
		return (strpos($this->__id, "[]") !== FALSE) ? str_replace("[]", "", $this->__id) : $this->__id;
	}

	public function getName($blnPlain = false) {
		if ($blnPlain) {
			$name = $this->__name;
		} else {
			switch ($this->__type) {
				case VFORM_RADIO_LIST:
					$name = $this->__name;
					break;
				case VFORM_CHECK_LIST:
					$name = (strpos($this->__name, "[]") === FALSE) ? $this->__name . "[]" : $this->__name;
					break;
			}
		}

		return $name;
	}

	public function addField($label, $value, $checked = false, $meta = array()) {
		$name = $this->getName();

		$objField = new VF_GroupField($this->getRandomId($name), $name, $this->__type, $label, $value, $checked, $meta);
		$objField->setMeta("parent", $this, true);

		$this->__fields->addObject($objField);

		//*** Set the default value if "checked" is set.
		if ($checked) $this->__default = $value;

		return $objField;
	}

}

?>