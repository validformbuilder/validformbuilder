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

require_once('class.classdynamic.php');
require_once('class.vf_base.php');
require_once('class.vf_collection.php');
require_once('class.vf_button.php');
require_once('class.vf_fieldset.php');
require_once('class.vf_note.php');
require_once('class.vf_text.php');
require_once('class.vf_password.php');
require_once('class.vf_textarea.php');
require_once('class.vf_checkbox.php');
require_once('class.vf_select.php');
require_once('class.vf_selectgroup.php');
require_once('class.vf_selectoption.php');
require_once('class.vf_file.php');
require_once('class.vf_hidden.php');
require_once('class.vf_paragraph.php');
require_once('class.vf_group.php');
require_once('class.vf_groupfield.php');
require_once('class.vf_area.php');
require_once('class.vf_multifield.php');
require_once('class.vf_captcha.php');
require_once('class.vf_fieldvalidator.php');
require_once('class.vf_page.php');
require_once('class.vf_condition.php');
require_once('class.vf_comparison.php');
require_once('class.vf_navigation.php');

require_once('vf_constants.php');

/**
 *
 * ValidForm Builder base class
 *
 * @package ValidForm
 * @author Felix Langfeldt, Robin van Baalen
 * @version Release: 0.2.7
 *
 */
class ValidForm extends ClassDynamic {
	protected $__description;
	protected $__meta;
	protected $__action;
	protected $__submitlabel;
	protected $__jsevents = array(); // Keep it lowercase to enable magic methods from ClassDynamic
	protected $__elements;
	protected $__name;
	protected $__mainalert;
	protected $__requiredstyle;
	protected $__novaluesmessage;
	protected $__invalidfields = array();

	/**
	 *
	 * Create an instance of the ValidForm Builder
	 * @param string|null $name The name and id of the form in the HTML DOM and JavaScript.
	 * @param string|null $description Desriptive text which is displayed above the form.
	 * @param string|null $action Form action. If left empty the form will post to itself.
	 * @param array $meta Array with meta data. The array gets directly parsed into the form tag with the keys as attribute names and the values as values.
	 */
	public function __construct($name = NULL, $description = NULL, $action = NULL, $meta = array()) {
		$this->__name = (is_null($name)) ? $this->__generateName() : $name;
		$this->__description = $description;
		$this->__submitlabel = "Submit";
		$this->__meta = $meta;

		$this->__elements = new VF_Collection();

		if (is_null($action)) {
			$this->__action = (isset($_SERVER['REQUEST_URI'])) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : $_SERVER['PHP_SELF'];
		} else {
			$this->__action = $action;
		}
	}

	/**
	 *
	 * Insert an HTML block into the form
	 * @param string $html
	 */
	public function addHtml($html) {
		$objString = new VF_String($html);
		$this->__elements->addObject($objString);

		return $objString;
	}

	/**
	 *
	 * Set the navigation of the form. Overides the default navigation (submit button).
	 * @param array $meta Array with meta data. Only the "style" attribute is supported as of now
	 */
	public function addNavigation($meta = array()) {
		$objNavigation = new VF_Navigation($meta);
		$this->__elements->addObject($objNavigation);

		return $objNavigation;
	}

	public function addFieldset($label = NULL, $noteHeader = NULL, $noteBody = NULL, $meta = array()) {
		$objFieldSet = new VF_Fieldset($label, $noteHeader, $noteBody, $meta);
		$this->__elements->addObject($objFieldSet);

		return $objFieldSet;
	}

	public function addHiddenField($name, $type, $meta = array(), $blnJustRender = false) {
		$objField = new VF_Hidden($name, $type, $meta);

		if(!$blnJustRender) {
			$this->__elements->addObject($objField);
		}

		return $objField;
	}

	public static function renderField($name, $label, $type, $validationRules, $errorHandlers, $meta) {
		$objField = null;
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
				$objField = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_PASSWORD:
				$objField = new VF_Password($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_CAPTCHA:
				$objField = new VF_Captcha($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_HTML:
			case VFORM_CUSTOM_TEXT:
			case VFORM_TEXT:
				$objField = new VF_Textarea($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_FILE:
				$objField = new VF_File($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_BOOLEAN:
				$objField = new VF_Checkbox($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_RADIO_LIST:
			case VFORM_CHECK_LIST:
				$objField = new VF_Group($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_SELECT_LIST:
				$objField = new VF_Select($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			default:
				$objField = new VF_Element($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
		}

		return $objField;
	}

	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array(), $blnJustRender = FALSE) {
		$objField = ValidForm::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);

		$objField->setRequiredStyle($this->__requiredstyle);

		if (!$blnJustRender) {
			//*** Fieldset already defined?
			$objFieldset = $this->__elements->getLast("VF_Fieldset");
			if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
				$objFieldset = $this->addFieldset();
			}

			$objField->setMeta("parent", $objFieldset, true);

			//*** Add field to the fieldset.
			$objFieldset->addField($objField);
		}

		return $objField;
	}

	public function addParagraph($strBody, $strHeader = "", $meta = array()) {
		$objParagraph = new VF_Paragraph($strHeader, $strBody, $meta);

		//*** Fieldset already defined?
		$objFieldset = $this->__elements->getLast("VF_Fieldset");
		if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
			$objFieldset = $this->addFieldset();
		}

		$objParagraph->setMeta("parent", $objFieldset, true);

		//*** Add field to the fieldset.
		$objFieldset->addField($objParagraph);

		return $objParagraph;
	}

	public function addButton($strLabel, $arrMeta = array()) {
		$objButton = new VF_Button($strLabel, $arrMeta);

		//*** Fieldset already defined?
		$objFieldset = $this->__elements->getLast("VF_Fieldset");
		if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
			$objFieldset = $this->addFieldset();
		}

		$objButton->setMeta("parent", $objFieldset, true);

		//*** Add field to the fieldset.
		$objFieldset->addField($objButton);

		return $objButton;
	}

	public function addArea($label = NULL, $active = FALSE, $name = NULL, $checked = FALSE, $meta = array()) {
		$objArea = new VF_Area($label, $active, $name, $checked, $meta);

		$objArea->setRequiredStyle($this->__requiredstyle);

		//*** Fieldset already defined?
		$objFieldset = $this->__elements->getLast("VF_Fieldset");
		if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
			$objFieldset = $this->addFieldset();
		}

		$objArea->setMeta("parent", $objFieldset, true);

		//*** Add field to the fieldset.
		$objFieldset->addField($objArea);

		return $objArea;
	}

	public function addMultiField($label = NULL, $meta = array()) {
		$objField = new VF_MultiField($label, $meta);

		$objField->setRequiredStyle($this->__requiredstyle);

		//*** Fieldset already defined?
		$objFieldset = $this->__elements->getLast("VF_Fieldset");
		if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
			$objFieldset = $this->addFieldset();
		}

		$objField->setMeta("parent", $objFieldset, true);

		//*** Add field to the fieldset.
		$objFieldset->addField($objField);

		return $objField;
	}

	public function addJSEvent($strEvent, $strMethod) {
		$this->__jsevents[$strEvent] = $strMethod;
	}

	public function toHtml($blnClientSide = true, $blnForceSubmitted = false, $strCustomJs = "") {
		$strOutput = "";

		if ($blnClientSide) {
			$strOutput .= $this->__toJS($strCustomJs);
		}

		$strClass = "validform vf__cf";

		if (is_array($this->__meta)) {
			if (isset($this->__meta["class"])) {
				$strClass .= " " . $this->__meta["class"];
			}
		}

		$strOutput .= "<form id=\"{$this->__name}\" method=\"post\" enctype=\"multipart/form-data\" action=\"{$this->__action}\" class=\"{$strClass}\">\n";

		//*** Main error.
		if ($this->isSubmitted() && !empty($this->__mainalert)) $strOutput .= "<div class=\"vf__main_error\"><p>{$this->__mainalert}</p></div>\n";

		if (!empty($this->__description)) $strOutput .= "<div class=\"vf__description\"><p>{$this->__description}</p></div>\n";

		$blnNavigation = false;
		foreach ($this->__elements as $element) {
			$strOutput .= $element->toHtml($this->isSubmitted($blnForceSubmitted), false, true, !$blnForceSubmitted);

			if (get_class($element) == "VF_Navigation") {
				$blnNavigation = true;
			}
		}

		if (!$blnNavigation) {
			$strOutput .= "<div class=\"vf__navigation vf__cf\">\n<input type=\"hidden\" name=\"vf__dispatch\" value=\"{$this->__name}\" />\n";
			$strOutput .= "<input type=\"submit\" value=\"{$this->__submitlabel}\" class=\"vf__button\" />\n</div>\n";
		}

		$strOutput .= "</form>";

		return $strOutput;
	}

	/**
	 * Serialize, compress and encode the entire form including it's values
	 * @param  boolean $blnSubmittedValues Whether or not to include submitted values or only serialize default values.
	 * @return String                      Base64 encoded, gzcompressed, serialized form.
	 */
	public function serialize($blnSubmittedValues = true) {
		// Validate & cache all values
		$this->valuesAsHtml($blnSubmittedValues); // Especially dynamic counters need this!

		return base64_encode(gzcompress(serialize($this)));
	}

	public static function unserialize($strSerialized) {
		return unserialize(gzuncompress(base64_decode($strSerialized)));
	}

	/**
	 * Check if the form is submitted by validating the value of the hidden
	 * vf__dispatch field.
	 * @param  boolean $blnForce 	Fake isSubmitted to true to force field values.
	 * @return boolean              [description]
	 */
	public function isSubmitted($blnForce = false) {
		if (ValidForm::get("vf__dispatch") == $this->__name || $blnForce) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getFields() {
		$objFields = new VF_Collection();

		foreach ($this->__elements as $objFieldset) {
			if ($objFieldset->hasFields()) {
				foreach ($objFieldset->getFields() as $objField) {
					if (is_object($objField)) {
						if ($objField->hasFields()) {
							foreach ($objField->getFields() as $objSubField) {
								if (is_object($objSubField)) {
									if ($objSubField->hasFields()) {
										foreach ($objSubField->getFields() as $objSubSubField) {
											if (is_object($objSubSubField)) {
												$objFields->addObject($objSubSubField);
											}
										}
									} else {
										$objFields->addObject($objSubField);
									}
								}
							}
						} else {
							$objFields->addObject($objField);
						}
					}
				}
			} else {
				$objFields->addObject($objFieldset);
			}
		}

		return $objFields;
	}

	public function getValidField($id) {
		$objReturn = NULL;

		$objFields = $this->getFields();
		foreach ($objFields as $objField) {
			if ($objField->getId() == $id) {
				$objReturn = $objField;
				break;
			}
		}

		return $objReturn;
	}

	public function getInvalidFields() {
		$objFields = $this->getFields();
		$arrReturn = array();

		foreach ($objFields as $objField) {
			$arrTemp = array();
			if (!$objField->isValid()) {
				$arrTemp[$objField->getName()] = $objField->getValidator()->getError();
				array_push($arrReturn, $arrTemp);
			}
		}

		return $arrReturn;
	}

	public function isValid() {
		return $this->__validate();
	}

	public function valuesAsHtml($hideEmpty = FALSE, $collection = null) {
		$strTable 		= "\t<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"validform\">\n";
		$strTableOutput	= "";
		$collection 	= (!is_null($collection)) ? $collection : $this->__elements;

		foreach ($collection as $objFieldset) {
			$strSet = "";
			$strTableOutput .= $this->fieldsetAsHtml($objFieldset, $strSet, $hideEmpty);
		}

		if (!empty($strTableOutput)) {
			return $strTable . $strTableOutput . "</table>";
		} else {
			if (!empty($this->__novaluesmessage)) {
				return $strTable . "<tr><td colspan=\"3\">{$this->__novaluesmessage}</td></tr></table>";
			} else {
				return "";
			}
		}
	}

	public function fieldsetAsHtml($objFieldset, &$strSet, $hideEmpty = false) {
		$strTableOutput = "";

		foreach ($objFieldset->getFields() as $objField) {
			if (is_object($objField)) {
				$strValue = (is_array($objField->getValue())) ? implode(", ", $objField->getValue()) : $objField->getValue();

				if ((!empty($strValue) && $hideEmpty) || (!$hideEmpty && !is_null($strValue))) {
					if ($objField->hasFields()) {
						switch (get_class($objField)) {
							case "VF_MultiField":
								$strSet .= $this->multiFieldAsHtml($objField, $hideEmpty);

								break;
							default:
								$strSet .= $this->areaAsHtml($objField, $hideEmpty);
						}
					} else {
						$strSet .= $this->fieldAsHtml($objField, $hideEmpty);
					}
				}

				if ($objField->isDynamic()) {
					$intDynamicCount = $objField->getDynamicCount();

					if ($intDynamicCount > 0) {
						for ($intCount = 1; $intCount <= $intDynamicCount; $intCount++) {
							switch (get_class($objField)) {
								case "VF_MultiField":
									$strSet .= $this->multiFieldAsHtml($objField, $hideEmpty, $intCount);

									break;

								case "VF_Area":
									$strSet .= $this->areaAsHtml($objField, $hideEmpty, $intCount);

									break;

								default:
									$strSet .= $this->fieldAsHtml($objField, $hideEmpty, $intCount);
							}
						}
					}
				}
				}
		}

		$strHeader = $objFieldset->getHeader();
		if (!empty($strHeader) && !empty($strSet)) {
			$strTableOutput .= "<tr>";
			$strTableOutput .= "<td colspan=\"3\">&nbsp;</td>\n";
			$strTableOutput .= "</tr>";
			$strTableOutput .= "<tr>";
			$strTableOutput .= "<td colspan=\"3\"><b>{$strHeader}</b></td>\n";
			$strTableOutput .= "</tr>";
		}

		if (!empty($strSet)) {
			$strTableOutput .= $strSet;
		}

		return $strTableOutput;
	}

	private function areaAsHtml($objField, $hideEmpty = FALSE, $intDynamicCount = 0) {
		$strReturn = "";
		$strSet = "";

		foreach ($objField->getFields() as $objSubField) {
			if (get_class($objSubField) !== "VF_Paragraph") {
				switch (get_class($objSubField)) {
					case "VF_MultiField":
						$strSet .= $this->multiFieldAsHtml($objSubField, $hideEmpty, $intDynamicCount);

						break;
					default:
						$strSet .= $this->fieldAsHtml($objSubField, $hideEmpty, $intDynamicCount);

						// Support nested dynamic fields.
						if ($objSubField->isDynamic()) {
							$intDynamicCount = $objSubField->getDynamicCount();
							for ($intCount = 1; $intCount <= $intDynamicCount; $intCount++) {
								$strSet .= $this->fieldAsHtml($objSubField, $hideEmpty, $intCount);
							}
						}
				}
			}
		}

		if (!empty($strSet)) {
			$strLabel = $objField->getLabel();
			if (!empty($strLabel)) {
				$strReturn = "<tr>";
				$strReturn .= "<td colspan=\"3\" style=\"white-space:nowrap\" class=\"vf__area_header\"><h3>{$objField->getLabel()}</h3></td>\n";
				$strReturn .= "</tr>";
			}

			$strReturn .= $strSet;
		} else {
			if (!empty($this->__novaluesmessage) && $objField->isActive()) {
				$strReturn = "<tr>";
				$strReturn .= "<td colspan=\"3\" style=\"white-space:nowrap\" class=\"vf__area_header\"><h3>{$objField->getLabel()}</h3></td>\n";
				$strReturn .= "</tr>";
				return $strReturn . "<tr><td colspan=\"3\">{$this->__novaluesmessage}</td></tr>";
			} else {
				return "";
			}
		}

		return $strReturn;
	}

	private function multiFieldAsHtml($objField, $hideEmpty = FALSE, $intDynamicCount = 0) {
		$strReturn = "";

		if ($objField->hasContent($intDynamicCount)) {
			if ($objField->hasFields()) {
				$strValue = "";
				$objSubFields = $objField->getFields();

				$intCount = 0;
				foreach ($objSubFields as $objSubField) {
					$intCount++;

					if (get_class($objSubField) == "VF_Hidden" && $objSubField->isDynamicCounter()) {
						continue;
					}

					$varValue = $objSubField->getValue($intDynamicCount);
					$strValue .= (is_array($varValue)) ? implode(", ", $varValue) : $varValue;
					$strValue .= ($objSubFields->count() > $intCount) ? " " : "";
				}

				$strValue = trim($strValue);

				if ((!empty($strValue) && $hideEmpty) || (!$hideEmpty && !empty($strValue))) {
					$strReturn .= "<tr class=\"vf__field_value\">";
					$strReturn .= "<td valign=\"top\" style=\"white-space:nowrap; padding-right: 20px\" class=\"vf__field\">{$objField->getLabel()}</td><td valign=\"top\" class=\"vf__value\"><strong>" . nl2br($strValue) . "</strong></td>\n";
					$strReturn .= "</tr>";
				}
			}
		}

		return $strReturn;
	}

	private function fieldAsHtml($objField, $hideEmpty = FALSE, $intDynamicCount = 0) {
		$strReturn = "";

		$strFieldName = $objField->getName();
		$strLabel = $objField->getLabel();
		$varValue = ($intDynamicCount > 0) ? $objField->getValue($intDynamicCount) : $objField->getValue();
		$strValue = (is_array($varValue)) ? implode(", ", $varValue) : $varValue;

		if ((!empty($strValue) && $hideEmpty) || (!$hideEmpty && !is_null($strValue))) {
			if ((get_class($objField) == "VF_Hidden") && $objField->isDynamicCounter()) {
				return $strReturn;
			} else {
				switch ($objField->getType()) {
					case VFORM_BOOLEAN:
						$strValue = ($strValue == 1) ? "yes" : "no";
						break;
				}

				if (empty($strLabel) && empty($strValue)) {
					//*** Skip the field.
				} else {
					$strReturn .= "<tr class=\"vf__field_value\">";
					$strReturn .= "<td valign=\"top\" style=\"padding-right: 20px\" class=\"vf__field\">{$objField->getLabel()}</td><td valign=\"top\" class=\"vf__value\"><strong>" . nl2br($strValue) . "</strong></td>\n";
					$strReturn .= "</tr>";
				}
			}
		}

		return $strReturn;
	}

	public static function get($param, $replaceEmpty = "") {
		$strReturn = (isset($_REQUEST[$param])) ? $_REQUEST[$param] : "";

		if (empty($strReturn) && !is_numeric($strReturn) && $strReturn !== 0) $strReturn = $replaceEmpty;

		return $strReturn;
	}

	protected function __toJS($strCustomJs = "", $arrInitArguments = array()) {
		$strReturn = "";
		$strJs = "";

		//*** Loop through all form elements and get their javascript code.
		foreach ($this->__elements as $element) {
			$strJs .= $element->toJS();
		}

		//*** Form Events.
		foreach ($this->__jsevents as $event => $method) {
			$strJs .= "\tobjForm.addEvent(\"{$event}\", {$method});\n";
		}

		// Indent javascript
		$strJs = str_replace("\n", "\n\t", $strJs);

		$strReturn .= "<script type=\"text/javascript\">\n";
		$strReturn .= "// <![CDATA[\n";
		$strReturn .= "function {$this->__name}_init() {\n";

		$strCalledClass = get_called_class();
		$strArguments = (count($arrInitArguments) > 0) ? "\"{$this->__name}\", \"{$this->__mainalert}\", " . implode(", ", $arrInitArguments) : "\"{$this->__name}\", \"{$this->__mainalert}\"";
		$strReturn .= "\tvar objForm = (typeof {$strCalledClass} !== \"undefined\") ? new {$strCalledClass}({$strArguments}) : new ValidForm(\"{$this->__name}\", \"{$this->__mainalert}\");\n";

		$strReturn .= $strJs;
		if (!empty($strCustomJs)) $strReturn .= $strCustomJs;
		$strReturn .= "\tobjForm.initialize();\n";
		$strReturn .= "\t$(\"#{$this->__name}\").data(\"vf__formElement\", objForm);";
		$strReturn .= "};\n";
		$strReturn .= "\n";
		$strReturn .= "try {\n";
		$strReturn .= "\tjQuery(function(){\n";
		$strReturn .= "\t\t{$this->__name}_init();\n";
		$strReturn .= "\t});\n";
		$strReturn .= "} catch (e) {\n";
		$strReturn .= "\talert('Exception caught while initiating ValidForm:\\n\\n' + e.message);\n";
		$strReturn .= "}\n";
		$strReturn .= "// ]]>\n";
		$strReturn .= "</script>\n";

		return $strReturn;
	}

	/**
	 * Generate a random name for the form.
	 * @return string the random name
	 */
	protected function __generateName() {
		return strtolower(get_class($this)) . "_" . mt_rand();
	}

	/**
	 * Generate a random number between 10000000 and 90000000.
	 * @return int the generated random number
	 */
	private function __random() {
		return rand(10000000, 90000000);
	}

	private function __validate() {
		$blnReturn = TRUE;

		foreach ($this->__elements as $element) {
			if (!$element->isValid()) {
				$blnReturn = FALSE;
				break;
			}
		}

		return $blnReturn;
	}

}

?>