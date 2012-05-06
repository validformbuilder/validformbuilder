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
 * SelectOption Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.1
 *
 */
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