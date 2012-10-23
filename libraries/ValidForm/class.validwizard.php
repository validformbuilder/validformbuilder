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

		$this->__uniqueid = (isset($meta["uniqueId"])) ? $meta["uniqueId"] : $this->generateId();
		$this->__nextlabel = (isset($meta["nextLabel"])) ? $meta["nextLabel"] : "Next &rarr;";
		$this->__previouslabel = (isset($meta["previousLabel"])) ? $meta["previousLabel"] : "&larr; Previous";
	}

	public function toHtml($blnClientSide = true, $blnForceSubmitted = false, $strJs = "", $blnFromSession = false) {
		$strReturn = null;

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
		$blnReturn = FALSE;

		if (ValidForm::get("vf__dispatch") == $this->__name) {
			//*** Try to retrieve the uniqueId from a REQUEST value.
			$strUniqueId = ValidWizard::get("vf__uniqueid");
			if (!empty($strUniqueId)) $this->__setUniqueId($strUniqueId);

			$blnReturn = TRUE;
		} else if ($blnForce) {
			$blnReturn = TRUE;
		}

		return $blnReturn;
	}

	/**
	 * Exactly the same as ValidForm->addMultiField. Only this time it's executed from the context of ValidWizard.
	 *
	 * @param [type] $label [description]
	 * @param array  $meta  [description]
	 */
	public function addMultiField($label = NULL, $meta = array()) {
		$objField = new VF_MultiField($label, $meta);

		$objField->setRequiredStyle($this->__requiredstyle);

		//*** Page already defined?
		$objPage = $this->__elements->getLast("VF_Page");
		if ($this->__elements->count() == 0 || !is_object($objPage)) {
			$objPage = $this->addPage();
		}

		//*** Fieldset already defined?
		$objFieldset = $objPage->getElements()->getLast("VF_Fieldset");
		if ($this->__elements->count() == 0 || !is_object($objFieldset)) {
			$objFieldset = $this->addFieldset();
		}

		//*** Add field to the fieldset.
		$objFieldset->addField($objField);

		return $objField;
	}

	public static function unserialize($strSerialized, $strUniqueId = "") {
		$objReturn = null;

		$objForm = unserialize(gzuncompress(base64_decode($strSerialized)));
		if (get_class($objForm) == "ValidWizard") {
			$objReturn = $objForm;
			if (!empty($strUniqueId)) $objReturn->__setUniqueId($strUniqueId);
		}

		return $objReturn;
	}

	private function __wizardJs($strCustomJs = "", $blnFromSession = false) {
		$strReturn = "";

		// Optionally set a custom first visibile page.
		$intPage = ($this->__currentpage > 1) ? $this->__currentpage : "";

		$strReturn .= "objForm.setLabel('next', '" . $this->__nextlabel . "');\n";
		$strReturn .= "objForm.setLabel('previous', '" . $this->__previouslabel . "');\n";
		$strReturn .= "objForm.initWizard({$intPage});\n" . $strCustomJs;

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

		$objPage = $this->__elements->getLast("VF_Page");
		if (!is_object($objPage)) {
			$objPage = $this->addPage();
		}

		$objPage->addField($objFieldSet);

		return $objFieldSet;
	}

	// public function valuesAsHtml($hideEmpty = false) {
	// 	$strOutput = "";
	// 	foreach ($this->__elements as $objPage) {
	// 		$strPage = "";
	// 		if (get_class($objPage) == "VF_Page") {
	// 			$strHeader = $objPage->getHeader();

	// 			$strPage .= "\n<div id='{$objPage->getId()}'>\n";

	// 			if (!empty($strHeader)) {
	// 				$strPage .= "<h2>{$strHeader}</h2>\n";
	// 			}

	// 			$strPageContent = parent::valuesAsHtml($hideEmpty, $objPage->getFields()) . "\n";

	// 			if (trim($strPageContent) !== "") {
	// 				$strOutput .= $strPage . $strPageContent . "</div>\n";
	// 			}
	// 		}
	// 	}

	// 	return $strOutput;
	// }

	public function valuesAsHtml($hideEmpty = false) {
		$strTable 		= "\t<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"validform\">\n";
		$strTableOutput	= "";

		foreach ($this->__elements as $objPage) {
			if (get_class($objPage) === "VF_Page") {
				$strTableOutput .= "<tr><td colspan=\"3\" class=\"vf__page-header\">{$objPage->getHeader()}</td></tr>";
				foreach ($objPage->getFields() as $objFieldset) {
					$strSet = "";
					$strTableOutput .= parent::fieldsetAsHtml($objFieldset, $strSet, $hideEmpty);
				}
			}
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
	 * Validate all form fields EXCLUDING the fields in the given page object and beyond.
	 * @param  string 	$strPageId 	The page object id
	 * @return boolean         		True if all fields validate, false if not.
	 */
	public function isValidUntil($strPageId) {
		$blnReturn = true;

		foreach ($this->__elements as $objPage) {
			if (!$blnReturn || $objPage->getId() == $strPageId) {
				break;
			}

			if (!$objPage->isValid()) {
				$blnReturn = false;
			}
		}

		return $blnReturn;
	}

	public function getInvalidFieldsUntil($strPageId) {
		$arrReturn = array();

		foreach ($this->__elements as $objPage) {
			if ($objPage->getId() == $strPageId) {
				break;
			}

			if ($objPage->hasFields()) {
				$objFieldsets = $objPage->getFields();
				foreach ($objFieldsets as $objFieldset) {
					foreach ($objFieldset->getFields() as $objField) {
						if (is_object($objField)) {
							if ($objField->hasFields()) {
								foreach ($objField->getFields() as $objSubField) {
									if (is_object($objSubField)) {
										if ($objSubField->hasFields()) {
											foreach ($objSubField->getFields() as $objSubSubField) {
												if (is_object($objSubSubField)) {
													if (!$objSubSubField->isValid()) {
														$arrTemp = array($objSubSubField->getName() => $objSubSubField->getValidator()->getError());
														array_push($arrReturn, $arrTemp);
													}
												}
											}
										} else {
											if (!$objSubField->isValid()) {
												$arrTemp = array($objSubField->getName() => $objSubField->getValidator()->getError());
												array_push($arrReturn, $arrTemp);
											}
										}
									}
								}
							} else {
								if (!$objField->isValid()) {
									$arrTemp = array($objField->getName() => $objField->getValidator()->getError());
									array_push($arrReturn, $arrTemp);
								}
							}
						}
					}
				}
			}
		}

		return $arrReturn;
	}

	/**
	 * getFields creates a flat collection of all form fields.
	 *
	 * @param  boolean $blnIncludeMultiFields Set this to true if you want to include MultiFields in the collection
	 * @return VF_Collection                  The collection of fields.
	 */
	public function getFields($blnIncludeMultiFields = false) {
		$objFields = new VF_Collection();

		foreach ($this->__elements as $objPage) {
			if ($objPage->hasFields()) {
				foreach ($objPage->getFields() as $objFieldset) {
					if ($objFieldset->hasFields()) {
						foreach ($objFieldset->getFields() as $objField) {
							if (is_object($objField)) {
								if ($objField->hasFields()) {
									// Also add the multifield to the resulting collection, if $blnIncludeMultiFields is true.
									if (get_class($objField) == "VF_MultiField" && $blnIncludeMultiFields) {
										$objFields->addObject($objField);
									}

									foreach ($objField->getFields() as $objSubField) {
										if (is_object($objSubField)) {
											if ($objSubField->hasFields()) {
												// Also add the multifield to the resulting collection, if $blnIncludeMultiFields is true.
												if (get_class($objField) == "VF_MultiField" && $blnIncludeMultiFields) {
													$objFields->addObject($objField);
												}

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
			} else {
				$objFields->addObject($objPage);
			}
		}

		return $objFields;
	}

	public function isValid($strPageId = null) {
		if (!is_null($strPageId)) {
			return $this->isValidUntil($strPageId);
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

	private function __setUniqueId($strId = "") {
		$this->__uniqueid = (empty($strId)) ? $this->generateId() : $strId;
	}
}

?>