<?php
/**
 * This file is part of ValidFormBuilder.
 *
 * Copyright (c) 2008 Felix Langfeldt
 *
 * ValidFormBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ValidFormBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ValidFormBuilder.  If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * ValidForm class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
 
require_once('class.vf_fieldset.php');
require_once('class.vf_note.php');
require_once('class.vf_text.php');
require_once('class.vf_password.php');
require_once('class.vf_textarea.php');
require_once('class.vf_checkbox.php');
require_once('class.vf_select.php');
require_once('class.vf_selectoption.php');
require_once('class.vf_file.php');
require_once('class.vf_paragraph.php');
require_once('class.vf_group.php');
require_once('class.vf_groupfield.php');
require_once('class.vf_area.php');
require_once('class.vf_captcha.php');
require_once('class.vf_fieldvalidator.php');
require_once('class.classdynamic.php');

define('VFORM_STRING', 1);
define('VFORM_TEXT', 2);
define('VFORM_NUMERIC', 3);
define('VFORM_INTEGER', 4);
define('VFORM_WORD', 5);
define('VFORM_EMAIL', 6);
define('VFORM_PASSWORD', 7);
define('VFORM_SIMPLEURL', 8);
define('VFORM_FILE', 9);
define('VFORM_BOOLEAN', 10);
define('VFORM_CAPTCHA', 11);
define('VFORM_RADIO_LIST', 12);
define('VFORM_CHECK_LIST', 13);
define('VFORM_SELECT_LIST', 14);
define('VFORM_PARAGRAPH', 15);
define('VFORM_CURRENCY', 16);
define('VFORM_DATE', 17);
define('VFORM_CUSTOM', 18);

class ValidForm extends ClassDynamic {
	private $__name;
	private $__description;
	private $__action;
	private $__elements = array();	
	private $__submitLabel;	
	protected $__mainalert;	
	protected $__requiredstyle;	
	
	public function __construct($name = NULL, $description = NULL) {
		/**
		 * Class constructor
		 * @param string|null $name the name/id of the form
		 */
		$this->__name = (is_null($name)) ? $this->__generateName() : $name;
		$this->__description = $description;
		$this->__action = $_SERVER["REQUEST_URI"];
	}
	
	public function setSubmitLabel($label) {
		/**
		 * Set the label of the forms submit button.
		 * @param string $label label of the button
		 */
		 
		 $this->__submitLabel = $label;
	}
	
	public function addFieldset($label, $noteHeader = NULL, $noteBody = NULL) {
		$objFieldSet = new VF_Fieldset($label, $noteHeader, $noteBody);
		array_push($this->__elements, $objFieldSet);
		
		return $objFieldSet;
	}
	
	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) {
		switch ($type) {
			case VFORM_STRING:
			case VFORM_WORD:
			case VFORM_EMAIL:
			case VFORM_SIMPLEURL:
			case VFORM_CUSTOM:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				
				$objField = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_PASSWORD:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				
				$objField = new VF_Password($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_CAPTCHA:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text_small";
				
				$objField = new VF_Captcha($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_CURRENCY:
			case VFORM_DATE:
			case VFORM_NUMERIC:
			case VFORM_INTEGER:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text_small";
				
				$objField = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_TEXT:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__text";
				if (!array_key_exists("rows", $meta)) $meta["rows"] = "5";
				if (!array_key_exists("cols", $meta)) $meta["cols"] = "21";
				
				$objField = new VF_Textarea($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_FILE:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__file";
				
				$objField = new VF_File($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_BOOLEAN:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__checkbox";
				
				$objField = new VF_Checkbox($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_RADIO_LIST:
			case VFORM_CHECK_LIST:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__radiobutton";
				
				$objField = new VF_Group($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			case VFORM_SELECT_LIST:
				if (!array_key_exists("class", $meta)) $meta["class"] = "vf__one";
				if (array_key_exists("multiple", $meta)) $meta["class"] = "vf__multiple";
				
				$objField = new VF_Select($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
			default:
				$objField = new VF_Element($name, $type, $label, $validationRules, $errorHandlers, $meta);
				break;
		}
		
		//*** Fieldset already defined?
		if (count($this->__elements) == 0) {
			$objFieldSet = new VF_Fieldset();
			array_push($this->__elements, $objFieldSet);
		}
		
		$objField->setRequiredStyle($this->__requiredstyle);
		
		$objFieldset = $this->__elements[count($this->__elements) - 1];
		$objFieldset->addField($objField);
		
		return $objField;
	}
	
	public function addParagraph($strBody, $strHeader = "") {
		$objParagraph = new VF_Paragraph($strHeader, $strBody);
		
		//*** Fieldset already defined?
		if (count($this->__elements) == 0) {
			$objFieldSet = new VF_Fieldset();
			array_push($this->__elements, $objFieldSet);
		}
		
		$objFieldset = $this->__elements[count($this->__elements) - 1];
		$objFieldset->addField($objParagraph);
		
		return $objParagraph;
	}
	
	public function addArea($label = NULL, $active = FALSE, $name = NULL, $checked = FALSE, $meta = array()) {
		$objArea = new VF_Area($label, $active, $name, $checked, $meta);
		
		//*** Fieldset already defined?
		if (count($this->__elements) == 0) {
			$objFieldSet = new VF_Fieldset();
			array_push($this->__elements, $objFieldSet);
		}
		
		$objArea->setRequiredStyle($this->__requiredstyle);
		
		$objFieldset = $this->__elements[count($this->__elements) - 1];
		$objFieldset->addField($objArea);
		
		return $objArea;
	}
	
	public function toHtml() {
		$strOutput = "<script type=\"text/javascript\">\n";
		$strOutput .= "// <![CDATA[\n";
		$strOutput .= "$(function(){\n";
		$strOutput .= $this->__toJS();		
		$strOutput .= "});\n";
		$strOutput .= "// ]]>\n";
		$strOutput .= "</script>\n";
		
		$strOutput .= "<form id=\"{$this->__name}\" method=\"post\" action=\"{$this->__action}\">\n";
		
		//*** Main error.
		if ($this->isSubmitted() && !empty($this->__mainalert)) $strOutput .= "<div class=\"vf__main_error\"><p>{$this->__mainalert}</p></div>\n";
		
		if (!empty($this->__description)) $strOutput .= "<div class=\"vf__description\"><p>{$this->__description}</p></div>\n";
		
		foreach ($this->__elements as $element) {
			$strOutput .= $element->toHtml($this->isSubmitted());
		}
		
		$strOutput .= "<div class=\"vf__navigation\">\n<input type=\"hidden\" name=\"vf__dispatch\" value=\"{$this->__name}\" />\n";
		$strOutput .= "<input type=\"submit\" value=\"{$this->__submitLabel}\" class=\"vf__button\" />\n</div>\n</form>\n";
	
		return $strOutput;
	}
	
	public function isSubmitted() {		
		if (ValidForm::get("vf__dispatch") == $this->__name) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function getFields() {
		$objFields = array();
		
		foreach ($this->__elements as $objFieldset) {
			foreach ($objFieldset->getFields() as $objField) {
				if (is_object($objField)) {
					array_push($objFields, $objField);
				}
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
	
	public function isValid() {
		return $this->__validate();
	}
	
	public function valuesAsHtml($hideEmpty = FALSE) {
		$strOutput = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
		
		foreach ($this->__elements as $objFieldset) {			
			$strSet = "";
			foreach ($objFieldset->getFields() as $objField) {
				if (is_object($objField)) {
					$strValue = (is_array($objField->getValue())) ? implode(", ", $objField->getValue()) : $objField->getValue();

					if ((!empty($strValue) && $hideEmpty) || (!$hideEmpty && !is_null($strValue))) {
						if ($objField->hasFields()) {
							$strSet .= "<tr>";
							$strSet .= "<td colspan=\"2\"><b>{$objField->getLabel()}</b></td>\n";
							$strSet .= "</tr>";

							foreach ($objField->getFields() as $objSubField) {
								$strValue = (is_array($objSubField->getValue())) ? implode(", ", $objSubField->getValue()) : $objSubField->getValue();

								switch ($objSubField->getType()) {
									case VFORM_BOOLEAN:
										$strValue = ($strValue == 1) ? "yes" : "no";
										break;
								}

								$strSet .= "<tr>";
								$strSet .= "<td>{$objSubField->getLabel()} &nbsp;&nbsp;&nbsp;</td><td>: <b>{$strValue}</b></td>\n";
								$strSet .= "</tr>";
							}
						} else {
							switch ($objField->getType()) {
								case VFORM_BOOLEAN:
									$strValue = ($strValue == 1) ? "yes" : "no";
									break;
							}

							$strSet .= "<tr>";
							$strSet .= "<td>{$objField->getLabel()} &nbsp;&nbsp;&nbsp;</td><td>: <b>{$strValue}</b></td>\n";
							$strSet .= "</tr>";
						}
					}
				}
			}
			
			$strHeader = $objFieldset->getHeader();
			if (!empty($strHeader) && !empty($strSet)) {
				$strOutput .= "<tr>";
				$strOutput .= "<td colspan=\"2\">&nbsp;</td>\n";
				$strOutput .= "</tr>";			
				$strOutput .= "<tr>";
				$strOutput .= "<td colspan=\"2\"><b>{$strHeader}</b></td>\n";
				$strOutput .= "</tr>";
			}
			
			$strOutput .= $strSet;
		}
		
		$strOutput .= "</table>";
		
		return $strOutput;
	}
		
	public static function get($param, $replaceEmpty = "") {
		(isset($_REQUEST[$param])) ? $strReturn = $_REQUEST[$param] : $strReturn = "";

		if (empty($strReturn) && !is_numeric($strReturn) && $strReturn !== 0) $strReturn = $replaceEmpty;

		return $strReturn;
	}
	
	private function __toJS() {
		$strReturn = "";
		
		$strReturn .= "var objForm = new ValidForm(\"{$this->__name}\", \"{$this->__mainalert}\");\n";
		foreach ($this->__elements as $element) {
			$strReturn .= $element->toJS();
		}
		
		return $strReturn;
	}
		
	private function __generateName() {
		/**
		 * Generate a random name for the form.
		 * @return string the random name
		 */
		return "validform_" . mt_rand();
	}
	
	private function __random() {
		/**
		 * Generate a random number between 10000000 and 90000000.
		 * @return int the generated random number
		 */
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