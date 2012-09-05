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
 * Group Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Group extends VF_Element {
	protected $__fields;

	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$this->__fields = new VF_Collection();

		parent::__construct($name, $type, $label, $validationRules, $errorHandlers, $meta);
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		$blnError = ($submitted && !$this->__validator->validate() && $blnDisplayErrors) ? TRUE : FALSE;
		
		$strClass = ($this->__validator->getRequired()) ? "vf__required" : "vf__optional";
		$strClass = ($blnError) ? $strClass . " vf__error" : $strClass;		
		$strOutput = "<div class=\"{$strClass}\">\n";
		
		if ($blnError) $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError()}</p>";
		
		$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		if (!empty($this->__label)) $strOutput .= "<label{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
		$strOutput .= "<fieldset class=\"vf__list\">\n";
		
		foreach ($this->__fields as $objField) {
			switch (get_class($objField)) {
				case "VF_GroupField":
					$strOutput .= $objField->toHtml($this->__getValue($submitted), $submitted, $this->__targetfield);
					
					break;
				default: //*** Targetfield.
					$strOutput .= $objField->toHtml($this->__getValue($submitted), $submitted, false);
					
					break;
			}
		}
		
		$strOutput .= "</fieldset>\n";
		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";		
		$strOutput .= "</div>\n";
		
		return $strOutput;
	}
	
	public function toJS() {
		$strOutput = "";
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
		
		$id 	= $this->getId();
		$name 	= $this->getName();

		$strOutput .= "objForm.addElement('{$id}', '{$name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

		foreach ($this->__fields as $field) {
			if ($field->hasTrigger()) {
				$strOutput .= $field->toJs();
			}
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
	
	public function addField($label, $value, $checked = FALSE, $meta = array()) {
		switch ($this->__type) {
			case VFORM_RADIO_LIST:
				$type = "radio";
				$name = $this->getName();
				break;
			case VFORM_CHECK_LIST:
				$type = "checkbox";
				$name = $this->getName();
				break;
		}
	
		$objField = new VF_GroupField($this->getRandomId($this->__name), $name, $type, $label, $value, $checked, $meta);
		$this->__fields->addObject($objField);
		
		return $objField;
	}

	public function addFieldObject($objTarget, $checked = false) {
		// For now, we only support one targetfield per group object.
		if (!is_object($this->__targetfield)) {
			// Add checkbox
			$objTrigger = $this->addField($objTarget->getLabel(), $objTarget->getName(true) . "_triggerfield", $checked);

			// Set the defaults on the target element
			$objTarget->setName($objTarget->getName(true) . "_triggerfield");
			$objTarget->setId($this->getRandomId($objTarget->getName()));

			// Set the trigger field.
			$objTarget->setTrigger($objTrigger);

			// This group has a trigger element.
			$this->__targetfield = $objTarget;

			// Add to validator
			$this->__validator->setTargetField($objTarget);

			$this->__fields->addObject($objTarget);
		}
	}
	
}

?>