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
 * @author     Robin van Baalen <rvanbaalen@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/

require_once('class.classdynamic.php');

/**
 * 
 * Area Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.1
 *
 */
class VF_Area extends ClassDynamic {
	protected $__label;
	protected $__active;
	protected $__name;
	protected $__checked;
	protected $__meta;
	protected $__dynamic;
	protected $__dynamicLabel;
	protected $__requiredstyle;
	protected $__fields;
	
	public function __construct($label, $active = FALSE, $name = NULL, $checked = FALSE, $meta = array()) {
		$this->__label = $label;
		$this->__active = $active;
		$this->__name = $name;
		$this->__checked = $checked;
		$this->__meta = $meta;

		$this->__fields = new VF_Collection();
		
		$this->__dynamic = (array_key_exists("dynamic", $meta)) ? $meta["dynamic"] : NULL;
		$this->__dynamicLabel = (array_key_exists("dynamicLabel", $meta)) ? $meta["dynamicLabel"] : NULL;
	}
	
	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$objField = ValidForm::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);
				
		$this->__fields->addObject($objField);

		if ($this->__dynamic || $objField->isDynamic()) {
			$objHiddenField = new VF_Hidden($objField->getId() . "_dynamic", VFORM_INTEGER, array("default" => "0", "dynamicCounter" => true));
			$this->__fields->addObject($objHiddenField);

			$objField->setDynamicCounter($objHiddenField);
		}
		
		return $objField;
	}
	
	public function addMultiField($label = NULL, $meta = array()) {
		if (!array_key_exists("dynamic", $meta)) $meta["dynamic"] = $this->__dynamic;

		//*** Overwrite dynamic settings. We cannot have a dynamic multifield inside a dynamic area.
		if ($this->__dynamic) {
			$meta["dynamic"] = $this->__dynamic;
			$meta["dynamicLabel"] = "";
		}

		$objField = new VF_MultiField($label, $meta);
		
		$objField->setRequiredStyle($this->__requiredstyle);
		
		$this->__fields->addObject($objField);
		
		return $objField;
	}
	
	public function toHtml($submitted = FALSE) {
		$strOutput = "";

		if ($this->__dynamic) {
			$intDynamicCount = $this->getDynamicCount();
			for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strOutput .= $this->__toHtml($submitted, $intCount);
			}
		} else {
			$strOutput = $this->__toHtml($submitted);
		}

		return $strOutput;
	}

	public function hasContent() {
		$blnReturn = false;

		foreach ($this->__fields as $objField) {
			if (get_class($objField) !== "VF_Hidden") {
				$varValue = $objField->getValue();
				// echo $varValue;

				if (!empty($varValue)) {
					$blnReturn = true;
				}

				break;
			}
		}

		return $varValue;
	}

	protected function __toHtml($submitted = false, $intCount = 0) {
		$strName 	= ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
		
		$strChecked = ($this->__active && $this->__checked && !$submitted) ? " checked=\"checked\"" : "";
		$strChecked = ($this->__active && $submitted && $this->hasContent()) ? " checked=\"checked\"" : "";
		// $strChecked = ($this->__active && !empty($value)) ? " checked=\"checked\"" : $strChecked;

		$strClass = (array_key_exists("class", $this->__meta)) ? " " . $this->__meta["class"] : "";
		$strClass = ($this->__active && empty($strChecked)) ? $strClass . " vf__disabled" : $strClass;
		
		$strOutput = "<fieldset class=\"vf__area{$strClass}\">\n";
		if ($this->__active) {
			$label = "<label for=\"{$strName}\"><input type=\"checkbox\" name=\"{$strName}\" id=\"{$strName}\" {$strChecked} /> {$this->__label}</label>";
		} else {
			$label = $this->__label;
		}
		if (!empty($this->__label)) $strOutput .= "<legend>{$label}</legend>\n";

		$blnHasContent = $this->hasContent();
		foreach ($this->__fields as $objField) {
			if (($intCount > 0) && get_class($objField) == "VF_Hidden" && $objField->isDynamicCounter()) {
				continue;
			}

			$submitted = ($this->__active && !$blnHasContent) ? FALSE : $submitted;
			$strOutput .= $objField->__toHtml($submitted, false, true, true, $intCount);
		}
		
		$strOutput .= "</fieldset>\n";

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
				switch (get_class($field)) {
					case "VF_MultiField":
						foreach ($field->getFields() as $subfield) {
							// Skip the hidden dynamic counter fields.
							if ((get_class($subfield) == "VF_Hidden") && $subfield->isDynamicCounter()) {
								continue;
							}
							$arrFields[$subfield->getId()] = $subfield->getName();
						}
						
						break;
					default:
						// Skip the hidden dynamic counter fields.
						if ((get_class($field) == "VF_Hidden") && $field->isDynamicCounter()) {
							continue;
						}
						$arrFields[$field->getId()] = $field->getName();
						break;
				}
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
			$strReturn .= $field->toJS();
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
		$value = ValidForm::get($this->__name);
		return (($this->__active && !empty($value)) || !$this->__active) ? TRUE : FALSE;
	}
	
	public function getId() {
		return null;
	}
	
	public function getType() {
		return 0;
	}
	
	public function hasFields() {
		return ($this->__fields->count() > 0) ? TRUE : FALSE;
	}
	
	private function __validate() {
		$value = ValidForm::get($this->__name);
		$blnReturn = TRUE;
		
		if ($this->__active && empty($value)) {
			//*** Not active;
		} else {
			foreach ($this->fields as $field) {
				if (!$field->isValid()) {
					$blnReturn = FALSE;
					break;
				}
			}
		}
		
		return $blnReturn;
	}
	
}

?>