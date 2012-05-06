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
 * Navigation Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.0
 *
 */
class VF_Navigation extends ClassDynamic {
	protected $__fields = array();
	protected $__meta;
	
	public function __construct($meta = array()) {
		$this->__meta = $meta;
	}
	
	public function addButton($label, $options = array()) {
		$objButton = new VF_Button($label, $options);
		
		array_push($this->__fields, $objButton);
		
		return $objButton;
	}
	
	public function addHtml($html) {
		$objString = new VF_String($html);
		array_push($this->__fields, $objString);
		
		return $objString;
	}
	
	public function toHtml($submitted = FALSE) {
		$strStyle = (isset($this->__meta["style"])) ? " style=\"{$this->__meta['style']}\"" : "";
		$strReturn = "<div class=\"vf__navigation\"{$strStyle}>\n";
		
		foreach ($this->__fields as $field) {
			$strReturn .= $field->toHtml($submitted);
		}
		
		$strReturn .= "</div>\n";
		
		return $strReturn;
	}
	
	public function toJS() {
		$strReturn = "";
		
		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS();
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return true;
	}
	
	public function getFields() {
		return $this->__fields;
	}
	
	public function getValue() {
		return TRUE;
	}
	
	public function getId() {
		return null;
	}
	
	public function getType() {
		return 0;
	}
	
	public function getHeader() {
		return;
	}
	
	public function hasFields() {
		return (count($this->__fields) > 0) ? TRUE : FALSE;
	}
	
}

?>