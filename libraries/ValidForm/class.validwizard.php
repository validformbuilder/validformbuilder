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

require_once("class.validform.php");

/**
 * ValidWizard Builder base class
 * 
 * @package ValidForm
 * @author 	Robin van Baalen <rvanbaalen@felix-it.com>
 * @version Release: 1.0
 *
 */
class ValidWizard extends ValidForm {
	public 		$__pagecount = 0;
	protected 	$__confirmlabel;
	protected 	$__currentpage = 1;
	protected 	$__previouslabel;
	protected 	$__nextlabel;
	private 	$__uniqueid;
	
	/**
	 * 
	 * Create an instance of the ValidForm Builder
	 * @param string|null $name The name and id of the form in the HTML DOM and JavaScript.
	 * @param string|null $description Desriptive text which is displayed above the form.
	 * @param string|null $action Form action. If left empty the form will post to itself.
	 * @param array $meta Array with meta data. The array gets directly parsed into the form tag with the keys as attribute names and the values as values.
	 */
	public function __construct($name = NULL, $description = NULL, $action = NULL, $meta = array()) {
		parent::__construct($name, $description, $action, $meta);

		$this->__setUniqueId();
		$this->__confirmlabel = (isset($meta["confirmLabel"])) ? $meta["confirmLabel"] : "Confirm";
		$this->__nextlabel = (isset($meta["nextLabel"])) ? $meta["nextLabel"] : "Next &rarr;";
		$this->__previouslabel = (isset($meta["previousLabel"])) ? $meta["previousLabel"] : "&larr; Previous";
	}

	public function toHtml($blnClientSide = true, $blnForceSubmitted = false, $strJs = "", $blnFromSession = false) {
		$strReturn = null;

		if (session_id() !== "") {
			// If we're inside a session, we're able to unserialize the form and edit it optionally.
			if (isset($_SESSION["vf__" . $this->getUniqueId()]) && !$blnFromSession) {
				$objForm = unserialize($_SESSION["vf__" . $this->getUniqueId()]);
			
				if (is_object($objForm)) {
					$strReturn = $objForm->toHtml($blnClientSide, true, $strJs, true);
				} 
			} else {
			    $strForm = $this->serialize();
			    $_SESSION["vf__" . $this->getUniqueId()] = $strForm;
			}
		}

		if (is_null($strReturn)) {
			$strReturn = parent::toHtml($blnClientSide, $blnForceSubmitted, $this->__wizardJs($strJs, $blnFromSession));
		}

		return $strReturn;
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

	private function __wizardJs($strCustomJs = "", $blnFromSession = false) {
		$strReturn = "";

		// Optionally set a custom first visibile page.
		$intPage = ($this->__currentpage > 1) ? $this->__currentpage : "";

		if ($blnFromSession && $this->doCorrect()) {
			// We're coming from the confirm page, so the first page we want to show is the last page of the wizard.
			$intPage = $this->__pagecount;
		}
		
		$strReturn .= ($this->__pagecount > 1) ? "objForm.setLabel('next', '" . $this->__nextlabel . "');\n" : "";
		$strReturn .= ($this->__pagecount > 1) ? "objForm.setLabel('previous', '" . $this->__previouslabel . "');\n" : "";
		$strReturn .= ($this->__pagecount > 1) ? "objForm.initWizard({$intPage});\n" . $strCustomJs : "";

		return $strReturn;
	}

	public function getPage($intIndex) {
		$intIndex--; // Convert page no. to index no.
		$this->__pages->seek($intIndex);
		return $this->__elements->current();
	}

	public function addPage($id = "", $header = "", $meta = array()) {
		if ($this->__elements->count() == 0) {
			// Add unique id field.
			$this->addHiddenField("vf__uniqueid", VFORM_STRING, array("default" => $this->getUniqueId()));
		}

		$objPage = new VF_Page($id, $header, $meta);
		$this->__elements->addObject($objPage);

		$this->__pagecount++;
		
		return $objPage;
	}
	
	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array(), $blnJustRender = FALSE) {
		$objField = ValidForm::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);
		
		//*** Fieldset already defined?
		if ($this->__elements->count() == 0 && !$blnJustRender) {
			$objPage = $this->addPage();
		}
		
		$objField->setRequiredStyle($this->__requiredstyle);
		
		if (!$blnJustRender) {
			$objPage = $this->__elements->getLast();
			$objPage->addField($objField);
		}
		
		return $objField;
	}
	
	public function addFieldset($label = NULL, $noteHeader = NULL, $noteBody = NULL, $options = array()) {
		$objFieldSet = new VF_Fieldset($label, $noteHeader, $noteBody, $options);
		
		$objPage = $this->__elements->getLast();
		$objPage->addField($objFieldSet);

		
		return $objFieldSet;
	}

	public function valuesAsHtml($hideEmpty = false) {
		$strOutput = "";
		foreach ($this->__elements as $objPage) {
			if (get_class($objPage) == "VF_Page") {
				$strHeader = $objPage->getHeader();
				$strOutput .= "\n<div id='{$objPage->getId()}'>\n";
				if (!empty($strHeader)) $strOutput .= "<h2>{$strHeader}</h2>\n";
				$strOutput .= parent::valuesAsHtml($hideEmpty, $objPage->getFields()) . "\n";
				$strOutput .= "</div>\n";
			}
		}

		return $strOutput;
	}
	
	/**
	 * Check if the form is confirmed by validating the value of the hidden
	 * vf__dispatch field.
	 * @param  boolean $blnForce 	Fake isConfirmed to true to force field values.
	 * @return boolean              [description]
	 */
	public function isConfirmed($blnForce = false) {
		if (ValidForm::get("vf__dispatch") == $this->__name . "_confirmed" || $blnForce) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function doCorrect() {
		if (ValidForm::get("vf__dispatch") == $this->__name . "_correct") {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function confirm() {
		// Save the current form.
		if (session_id() !== "") {
			// If we're inside a session, we're able to serialize the form to a session variable for later use.
			$_SESSION["vf__" . $this->getUniqueId()] = $this->serialize();
		}

		$strOutput = "";
		$strName = $this->__name . "_confirmed";

		$strOutput .= $this->__confirmJs();

		$strOutput .= "<form id=\"{$this->__name}\" method=\"post\" enctype=\"multipart/form-data\" action=\"{$this->__action}\" class=\"validform vf__cf\">\n";
		$strOutput .= "<div class='vf__confirm'>";
		$strOutput .= $this->valuesAsHtml();
		$strOutput .= "</div>";
		$strOutput .= $this->__addHiddenFields();
		$strOutput .= "<div class=\"vf__navigation vf__cf\">\n<input type=\"hidden\" name=\"vf__dispatch\" value=\"{$strName}\" />\n";
		$strOutput .= "<input type=\"hidden\" name=\"vf__uniqueid\" value=\"{$this->getUniqueId()}\" />\n";
		$strOutput .= "<input type=\"submit\" value=\"{$this->__confirmlabel}\" class=\"vf__button\" />\n";
		if (session_id() !== "") {
			// If we're inside a session, we're able to provide a back button on the confirm page, to edit previously entered values.
			$strOutput .= "<input type=\"submit\" value=\"{$this->__previouslabel}\" class=\"vf__button vf__previous\" id=\"confirm_" . $this->__name . "_previous\"/>\n";
		}
		$strOutput .= "</div>\n";
		$strOutput .= "</form>";

		return $strOutput;
	}

	protected function __confirmJs() {
		$strOutput = "";

		$strOutput .= "<script>";
		$strOutput .= "jQuery(function ($) {\n";
		$strOutput .= "try {\n";
		$strOutput .= "objConfirmForm = new ValidConfirmForm('" . $this->__name . "');\n";
		$strOutput .= "} catch (e) {\n";
		$strOutput .= "throw new Error(\"Failed to initialize ValidConfirmForm('" . $this->__name . "'). Error:\\n\" + e.message);\n";
		$strOutput .= "}\n";
		$strOutput .= "});";
		$strOutput .= "</script>";

		return $strOutput;
	}

	private function __addHiddenFields() {
		$strOutput = "";
		foreach ($this->getElements() as $objPage) {
			if (get_class($objPage) == "VF_Hidden") continue;

			foreach ($objPage->getElements() as $objFieldSet) {
				foreach ($objFieldSet->getFields() as $objField) {
					if ($objField->hasFields()) {
						foreach ($objField->getFields() as $objSubField) {
							if (get_class($objSubField) == "VF_Hidden") {
								$strOutput .= $objSubField->toHtml(true);
							}
						}
					} else {
						if (get_class($objField) == "VF_Hidden") {
							$strOutput .= $objField->toHtml(true);
						}
					}
				}
			}
		}

		return $strOutput;
	}

	/**
	 * Validate all form fields until and including the fields in the given page object
	 * @param  string 	$strPageId 	The page object id
	 * @return boolean         		True if all fields validate, false if not.
	 */
	public function validateUntil($strPageId) {
		$blnValid = true;
		foreach ($this->__elements as $objPage) {
			if (!$objPage->isValid()) {
				$blnValid = false;
			}

			if ($objPage->getId() == $strPageId) {
				break;
			}
		}

		return $blnValid;
	}

	public function isValid($strPageId = null) {
		if (!is_null($strPageId)) {
			return $this->validateUntil($strPageId);
		} else {
			return parent::isValid();
		}
	}

	public function generateId($intLength = 8) {
		$strChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$strReturn = '';
		
		srand((double)microtime()*1000000);

		for ($i = 1; $i <= $intLength; $i++) {
			$intNum = rand() % (strlen($strChars) - 1);
			$strTmp = substr($strChars, $intNum, 1);
			$strReturn .= $strTmp;
		}

		return $strReturn;
	}

	public function getUniqueId() {
		return ValidForm::get("vf__uniqueid", $this->__uniqueid);
	}

	private function __setUniqueId() {
		$this->__uniqueid = $this->generateId();
	}
}

?>