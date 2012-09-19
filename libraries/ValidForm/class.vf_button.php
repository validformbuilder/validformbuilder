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

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {					
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
	
	public function isDynamic() {
		return false;
	}
	
	public function hasFields() {
		return FALSE;
	}
	
}

?>