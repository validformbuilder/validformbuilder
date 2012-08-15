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
 * 
 * ValidWizard Builder base class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.7
 *
 */
class ValidWizard extends ValidForm {
	public $__pageCount = 1;
	protected $__confirmlabel;
	private $__nextlabel;
	private $__objCurrentPage;
	private $__uniqueid;
	
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
	}

	public function toHtml($blnClientSide = true, $blnForceSubmitted = false, $strJs = "") {
		$strJs = ($this->__pageCount > 1) ? "objForm.initWizard();\n" . $strJs : "";
		return parent::toHtml($blnClientSide, $blnForceSubmitted, $strJs);
	}

	public function getPage($intIndex) {
		$intIndex--; // Convert page no. to index no.
		$this->__pages->seek($intIndex);
		return $this->__elements->current();
	}

	public function addPage($id = "", $header = "", $meta = array()) {
		if ($this->__elements->count() == 0) {
			// Add unique id field.
			$this->addHiddenField("vf__uniqueid", VFORM_STRING, array("default" => $this->__uniqueid));
		}

		$objPage = new VF_Page($id, $header, $meta);
		$this->__elements->addObject($objPage);

		$this->__objCurrentPage = $objPage;
		$this->__pageCount++;
		
		return $objPage;
	}
	
	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array(), $blnJustRender = FALSE) {
		$objField = parent::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta, $blnJustRender);
		
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

	public function confirm() {
		$strOutput = "";
		$strName = $this->__name . "_confirmed";

		$strOutput .= "<form id=\"{$this->__name}\" method=\"post\" enctype=\"multipart/form-data\" action=\"{$this->__action}\" class=\"validform\">\n";
		$strOutput .= "<div class='vf__confirm'>";
		$strOutput .= $this->valuesAsHtml();
		$strOutput .= "</div>";
		$strOutput .= "<div class=\"vf__navigation\">\n<input type=\"hidden\" name=\"vf__dispatch\" value=\"{$strName}\" />\n<input type=\"hidden\" name=\"vf__uniqueid\" value=\"{$this->__uniqueid}\" />\n";
		$strOutput .= "<input type=\"submit\" value=\"{$this->__confirmlabel}\" class=\"vf__button\" />\n</div>\n";
		$strOutput .= "</form>";

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
		return $this->__uniqueid;
	}

	private function __setUniqueId() {
		$this->__uniqueid = $this->generateId();
	}
}

?>