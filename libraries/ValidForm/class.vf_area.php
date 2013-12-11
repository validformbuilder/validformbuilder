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
 * Area Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Area extends VF_Base {
	protected $__label;
	protected $__active;
	protected $__checked;
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

		//*** Set label & field specific meta
		$this->__initializeMeta();

		$this->__fields = new VF_Collection();

		$this->__dynamic = $this->getMeta("dynamic", null);
		$this->__dynamicLabel = $this->getMeta("dynamicLabel", null);
	}

	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		$objField = ValidForm::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);

		$objField->setMeta("parent", $this, true);

		$this->__fields->addObject($objField);

		if ($this->__dynamic || $objField->isDynamic()) {
		    //*** The dynamic count can be influenced by a meta value.
		    $intDynamicCount = (isset($meta["dynamicCount"])) ? $meta["dynamicCount"] : 0;

			$objHiddenField = new VF_Hidden($objField->getId() . "_dynamic", VFORM_INTEGER, array("default" => $intDynamicCount, "dynamicCounter" => true));
			$this->__fields->addObject($objHiddenField);

			$objField->setDynamicCounter($objHiddenField);
		}

		return $objField;
	}

	public function addParagraph($strBody, $strHeader = "", $meta = array()) {
		$objParagraph = new VF_Paragraph($strHeader, $strBody, $meta);

		$objParagraph->setMeta("parent", $this, true);

		//*** Add field to the fieldset.
		$this->__fields->addObject($objParagraph);

		return $objParagraph;
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
		$objField->setMeta("parent", $this, true);

		$this->__fields->addObject($objField);

		return $objField;
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

	public function hasContent($intCount = 0) {
		$blnReturn = false;

		foreach ($this->__fields as $objField) {
			if (get_class($objField) !== "VF_Hidden" || get_class($objField) !== "VF_Paragraph") {
				if (get_class($objField) == "VF_MultiField") {
					$blnReturn = $objField->hasContent($intCount);
					if ($blnReturn) {
						break;
					}
				} else {
					if ($objField instanceof VF_Element) {
						$varValue = $objField->getValidator()->getValue($intCount);

						if (!empty($varValue)) {
							$blnReturn = true;
							break;
						}
					}
				}
			}
		}

		return $blnReturn;
	}

	protected function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0) {
		//*** Conditional meta should be set before all other meta. Otherwise the set meta is being reset.
		$this->setConditionalMeta();

		$strName 	= ($intCount == 0) ? $this->getName() : $this->getName() . "_" . $intCount;

		if ($this->__active && $this->__checked && !$submitted) $this->setFieldMeta("checked", "checked", true);
		if ($this->__active && $submitted && $this->hasContent($intCount)) $this->setFieldMeta("checked", "checked", true);

		$this->setMeta("class", "vf__area");
		if ($this->__active && is_null($this->getFieldMeta("checked", null))) $this->setMeta("class", "vf__disabled");
		if ($intCount > 0) $this->setMeta("class", "vf__clone");

		$strId = ($intCount == 0) ? " id=\"{$this->getId()}\"" : "";
		$strOutput = "<fieldset{$this->__getMetaString()}{$strId}>\n";

		if ($this->__active) {
			$strCounter = ($intCount == 0 && $this->__dynamic) ? " <input type='hidden' name='{$strName}_dynamic' value='{$intCount}' id='{$strName}_dynamic'/>" : "";
			$label = "<label for=\"{$strName}\"><input type=\"checkbox\" name=\"{$strName}\" id=\"{$strName}\"{$this->__getFieldMetaString()} /> {$this->__label}{$strCounter}</label>";
		} else {
			$label = $this->__label;
		}

		if (!empty($this->__label)) $strOutput .= "<legend>{$label}</legend>\n";

		$blnHasContent = $this->hasContent($intCount);
		foreach ($this->__fields as $objField) {
			if (($intCount > 0) && get_class($objField) == "VF_Hidden" && $objField->isDynamicCounter()) {
				continue;
			}

			//$submitted = ($this->__active && !$blnHasContent) ? FALSE : $submitted;
			$strOutput .= $objField->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
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

			$strReturn .= "<div class=\"vf__dynamic vf__cf\"{$this->getDynamicButtonMeta()}>";
			$strReturn .= "<a href=\"#\" data-target-id=\"" . implode("|", array_keys(array_filter($arrFields))) . "\" data-target-name=\"" . implode("|", array_values(array_filter($arrFields))) . "\">{$this->__dynamicLabel}</a>";
			$strReturn .= "</div>";
		}

		return $strReturn;
	}

	public function toJS() {
		$strReturn = "";

		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS($this->__dynamic);
		}

		$strReturn .= $this->conditionsToJs();

		return $strReturn;
	}

	public function isActive() {
		return $this->__active;
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
		return $this->__dynamic;
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

	public function getValue($intCount = null) {
		$strName = ($intCount > 0) ? $this->__name . "_" . $intCount : $this->__name;
		$value = ValidForm::get($strName);
		return (($this->__active && !empty($value)) || !$this->__active) ? TRUE : FALSE;
	}

	public function getId() {
		return $this->getName();
	}

	public function getType() {
		return 0;
	}

	public function hasFields() {
		return ($this->__fields->count() > 0) ? true : false;
	}

	private function __validate($intCount = null) {
		$blnReturn = true;

		foreach ($this->__fields as $field) {
			// Note: hasContent is only accurate if isValid() is called first ...
			if (!$field->isValid($intCount)) {
				$blnReturn = false;
				break;
			}
		}

		// ... therefore, check if the area is empty after validation of all the fields.
		if ($this->__active && !$this->hasContent($intCount)) {
			$blnReturn = true;
		}


		return $blnReturn;
	}

}

?>