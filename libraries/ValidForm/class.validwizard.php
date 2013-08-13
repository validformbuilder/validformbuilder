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

require_once("class.validform.php");

/**
 * ValidWizard Builder base class
 *
 * @package ValidForm
 * @author 	Robin van Baalen <rvanbaalen@felix-it.com>
 * @version 1.0
 *
 */
class ValidWizard extends ValidForm {
	public 		$__pagecount = 0;
	protected 	$__currentpage = 1;
	protected 	$__previouslabel;
	protected 	$__nextlabel;
	protected 	$__hasconfirmpage = false;

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

		$this->__nextlabel = (isset($meta["nextLabel"])) ? $meta["nextLabel"] : "Next &rarr;";
		$this->__previouslabel = (isset($meta["previousLabel"])) ? $meta["previousLabel"] : "&larr; Previous";
	}

	public function toHtml($blnClientSide = true, $blnForceSubmitted = false, $strJs = "") {
		$strReturn = null;

		if (is_null($strReturn)) {
			$strReturn = parent::toHtml($blnClientSide, $blnForceSubmitted);
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

		$objField->setMeta("parent", $objFieldset, true);

		//*** Add field to the fieldset.
		$objFieldset->addField($objField);

		return $objField;
	}

	/**
	 * Get a page from the collection based on it's zero-based position in the elements collection
	 * @param  Integer $intIndex Zero-based position
	 * @return VF_Page           VF_Page element, if found.
	 */
	public function getPage($intIndex = 0) {
		$intIndex--; // Convert page no. to index no.
		$this->__elements->seek($intIndex);

		$objReturn = $this->__elements->current();
		if ($objReturn === false || get_class($objReturn) !== "VF_Page") {
			$objReturn = null;
		}

		return $objReturn;
	}

	public function addPage($id = "", $header = "", $meta = array()) {
		$objPage = new VF_Page($id, $header, $meta);
		$this->__elements->addObject($objPage);

		if ($this->__elements->count() == 1) {
			// Add unique id field.
			$this->addHiddenField("vf__uniqueid", VFORM_STRING, array("default" => $this->getUniqueId()));
		}

		$this->__pagecount++;

		return $objPage;
	}

	/**
	 * Wrapper method for setting the $__hasconfirmpage property
	 */
	public function addConfirmPage() {
		$this->__hasconfirmpage = true;
	}
	public function removeConfirmPage() {
		$this->__hasconfirmpage = false;
	}
	public function hasConfirmPage() {
		return !!$this->__hasconfirmpage;
	}

	public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array(), $blnJustRender = FALSE) {
		$objField = parent::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);

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

	public function valuesAsHtml($hideEmpty = false) {
		$strTable 		= "\t<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"validform\">\n";
		$strTableOutput	= "";

		foreach ($this->__elements as $objPage) {
			if (get_class($objPage) === "VF_Page") {
				$strHeader = $objPage->getShortHeader(); // Passing 'true' will return the optional 'short header' if available.

				$strTableOutput .= "<tr><td colspan=\"3\" class=\"vf__page-header\">{$strHeader}</td></tr>";
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

	public static function unserialize($strSerialized, $strUniqueId = "") {
		$objReturn = parent::unserialize($strSerialized);

		if (get_class($objReturn) == "ValidWizard" && !empty($strUniqueId)) {
			$objReturn->__setUniqueId($strUniqueId);
		}

		return $objReturn;
	}

	protected function __toJs($strCustomJs = "", $blnFromSession = false) {
		// Add extra arguments to javascript initialization method.
		$arrInitArguments = array();
		if($this->__currentpage > 1) $arrInitArguments["initialPage"] = $this->__currentpage;
		$arrInitArguments["confirmPage"] = $this->__hasconfirmpage;

		$strJs = "";
		$strJs .= "objForm.setLabel('next', '" . $this->__nextlabel . "');\n\t";
		$strJs .= "objForm.setLabel('previous', '" . $this->__previouslabel . "');\n\t";

		if (strlen($strCustomJs) > 0) {
			$strJs .= $strCustomJs;
		}

		return parent::__toJs($strJs, $arrInitArguments);
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
}

?>