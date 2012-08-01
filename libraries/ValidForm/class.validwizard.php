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
	private $__nextlabel;
	private $__pageCount = 1;
	private $__objCurrentPage;
	
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
	}

	public function getPage($intIndex) {
		$intIndex--; // Convert page no. to index no.
		$this->__pages->seek($intIndex);
		return $this->__elements->current();
	}

	public function addPage($header = "", $meta = array()) {
		$objPage = new VF_Page($header, $meta);
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
	
}

?>