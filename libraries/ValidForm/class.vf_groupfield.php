<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_GroupField class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.0
 */
  
require_once('class.vf_element.php');

class VF_GroupField extends VF_Element {
	protected $__id;
	protected $__name;
	protected $__label;
	protected $__value;
	protected $__type;
	protected $__checked;
	protected $__meta;
	
	public function __construct($id, $name, $type, $label, $value, $checked = FALSE, $meta = array()) {
		$this->__id = $id;
		$this->__name = $name;
		$this->__type = $type;
		$this->__label = $label;
		$this->__value = $value;
		$this->__checked = $checked;
		$this->__meta = $meta;
	}
	
	public function toHtml($value = NULL, $submitted = FALSE) {
		if (is_array($value)) {
			foreach ($value as $valueItem) {
				if ($valueItem == $this->__value) {
					$strChecked = " checked=\"checked\"";
					break;
				} else {
					$strChecked = "";
				}
			}
		} else {
			$strChecked = ($this->__checked && is_null($value) && !$submitted) ? " checked=\"checked\"" : "";
			$strChecked = ($value == $this->__value) ? " checked=\"checked\"" : $strChecked;
		}
				
		$strOutput = "<label for=\"{$this->__id}\">\n";
		$strOutput .= "<input type=\"{$this->__type}\" value=\"{$this->__value}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$strChecked} {$this->__getMetaString()} /> {$this->__label}\n";
		$strOutput .= "</label>\n";
		
		return $strOutput;
	}
	
}

?>