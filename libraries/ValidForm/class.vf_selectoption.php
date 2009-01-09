<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_SelectOption class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.0
 */
  
require_once('class.classdynamic.php');

class VF_SelectOption extends ClassDynamic {
	protected $__label;
	protected $__value;
	protected $__selected;
	
	public function __construct($label, $value, $selected = FALSE) {
		$this->__label = $label;
		$this->__value = $value;
		$this->__selected = $selected;
	}
	
	public function toHtml($value = NULL) {
		$strSelected = ($this->__selected && is_null($value)) ? " selected=\"selected\"" : "";
		$strSelected = ($value == $this->__value) ? " selected=\"selected\"" : $strSelected;
		$strOutput = "<option value=\"{$this->__value}\"{$strSelected}>{$this->__label}</option>\n";
		
		return $strOutput;
	}
	
}

?>