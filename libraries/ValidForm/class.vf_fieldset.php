<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Fieldset class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.0
 */
  
require_once('class.classdynamic.php');

class VF_Fieldset extends ClassDynamic {
	protected $__header;
	protected $__note;
	protected $__fields = array();
	
	public function __construct($header = NULL, $noteHeader = NULL, $noteBody = NULL) {
		$this->__header = $header;
		
		if (!is_null($noteHeader) || !is_null($noteBody)) {
			$this->__note = new VF_Note($noteHeader, $noteBody);
		}
	}
	
	public function addField($field) {
		array_push($this->__fields, $field);
	}
	
	public function toHtml($submitted = FALSE) {
		$strOutput = "<fieldset>\n";
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