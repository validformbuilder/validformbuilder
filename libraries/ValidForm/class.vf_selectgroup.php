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
 * SelectGroup Class
 * 
 * @package ValidForm
 * @author Robin van Baalen
 * @version Release: 0.2.1
 *
 */
class VF_SelectGroup extends ClassDynamic {
	protected $__options = array();
	protected $__label;
	
	public function __construct($label) {
		$this->__label = $label;
	}
	
	public function toHtml($value = NULL) {		
		$strOutput = "<optgroup label=\"{$this->__label}\">\n";
		foreach ($this->__options as $option) {
			$strOutput .= $option->toHtml($value);
		}
		$strOutput .= "</optgroup>\n";
		
		return $strOutput;
	}
	
	public function addField($label, $value, $selected = FALSE) {
		$objOption = new VF_SelectOption($label, $value, $selected);
		array_push($this->__options, $objOption);
		
		return $objOption;
	}
	
}

?>