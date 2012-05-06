<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.opensource.org/licenses/mit-license.php
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/
  
require_once('class.classdynamic.php');

/**
 * 
 * Button Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.0
 *
 */
class VF_Button extends ClassDynamic {
	protected $__id;
	protected $__label;
	protected $__type;
	protected $__class;
	protected $__disabled;
	
	public function __construct($label, $meta = array()) {
		$this->__label = $label;
		$this->__type = (isset($meta["type"])) ? $meta["type"] : "submit";
		$this->__class = (isset($meta["class"])) ? $meta["class"] : "vf__button";
		$this->__disabled = (isset($meta["disabled"])) ? $meta["disabled"] : "";
	}

	public function toHtml($submitted = FALSE) {					
		$strDisabled = (!empty($this->__disabled)) ? "disabled=\"disabled\"" : ""; 	
		$strReturn = "<input type=\"{$this->__type}\" value=\"{$this->__label}\" class=\"{$this->__class}\" $strDisabled />\n";
				
		return $strReturn;
	}
	
	public function toJS() {
		return null;
	}
	
	public function isValid() {
		return TRUE;
	}
	
	public function hasFields() {
		return FALSE;
	}
	
}

?>