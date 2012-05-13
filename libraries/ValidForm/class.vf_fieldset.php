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
  
require_once('class.classdynamic.php');

/**
 * 
 * Fieldset Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Fieldset extends ClassDynamic {
	protected $__header;
	protected $__note;
	protected $__class;
	protected $__style;
	protected $__fields = array();
	
	public function __construct($header = NULL, $noteHeader = NULL, $noteBody = NULL, $meta = array()) {
		$this->__header = $header;
		$this->__class = (isset($meta["class"])) ? $meta["class"] : "";
		$this->__style = (isset($meta["style"])) ? $meta["style"] : "";
		
		if (!empty($noteHeader) || !empty($noteBody)) {
			$this->__note = new VF_Note($noteHeader, $noteBody);
		}
	}
	
	public function addField($field) {
		array_push($this->__fields, $field);
	}
	
	public function toHtml($submitted = FALSE) {
		$strClass = (!empty($this->__class)) ? " class=\"{$this->__class}\"" : ""; 
		$strStyle = (!empty($this->__style)) ? " style=\"{$this->__style}\"" : ""; 
		$strOutput = "<fieldset{$strClass}{$strStyle}>\n";
		if (!empty($this->__header)) $strOutput .= "<legend><span>{$this->__header}</span></legend>\n";
		
		if (is_object($this->__note)) $strOutput .= $this->__note->toHtml();
		
		foreach ($this->__fields as $field) {
			$strOutput .= $field->toHtml($submitted);
		}
		
		$strOutput .= "</fieldset>\n";
	
		return $strOutput;
	}
	
	public function toJS() {
		$strReturn = "";
		
		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS();
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return $this->__validate();
	}
	
	public function hasFields() {
		return (count($this->__fields) > 0) ? TRUE : FALSE;
	}
	
	public function getFields() {
		return $this->__fields;
	}
	
	private function __validate() {
		$blnReturn = TRUE;
		
		foreach ($this->__fields as $field) {
			if (!$field->isValid()) {
				$blnReturn = FALSE;
				break;
			}
		}
		
		return $blnReturn;
	}
	
}

?>