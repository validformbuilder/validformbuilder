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
 * String Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.0
 *
 */
class VF_String extends ClassDynamic {
	protected $__id;
	protected $__body;
	
	public function __construct($bodyString) {
		$this->__body = $bodyString;
	}
		
	public function toHtml($submitted = FALSE) {
		return $this->__body;
	}
	
	public function toJS() {
		return;
	}
	
	public function isValid() {
		return TRUE;
	}
	
	public function hasFields() {
		return FALSE;
	}
	
	public function getValue() {
		return;
	}
	
	public function isDynamic() {
		return false;
	}
	
}

?>