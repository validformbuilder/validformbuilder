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
 * @author Robin van Baalen
 * @version 0.1.1
 */
  
require_once('class.classdynamic.php');

class VF_SelectGroup extends ClassDynamic {
	protected $__options = array();
	protected $__label;
	protected $__selected;
	
	public function __construct($label, $selected = false) {
		$this->__label = $label;
		$this->__selected = $selected;
	}
	
	public function toHtml() {
		
		$strOutput = "<optgroup label=\"{$this->__label}\">\n";
		foreach ($this->__options as $option) {
			$strOutput .= $option->toHtml($this->__selected);
		}
		$strOutput .= "</optgroup>\n";
		
		return $strOutput;
	}
	
	public function addField($value, $label, $selected = FALSE) {
		$objOption = new VF_SelectOption($value, $label, $selected);
		array_push($this->__options, $objOption);
		
		return $objOption;
	}
	
}

?>