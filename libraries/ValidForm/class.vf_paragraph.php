<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Paragraph class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1.2
 */
  
require_once('class.classdynamic.php');

class VF_Paragraph extends ClassDynamic {
	protected $__header;
	protected $__body;
	protected $__id;
	
	public function __construct($header = NULL, $body = NULL) {
		$this->__header = $header;
		$this->__body = $body;
	}
	
	public function toHtml($submitted = FALSE) {
		$strOutput = "<div>\n";
		
		if (!empty($this->__header)) $strOutput .= "<h3>{$this->__header}</h3>\n";
		if (!empty($this->__body)) {
			if (preg_match("/<p.*?>/", $this->__body) > 0) {
				$strOutput .= "{$this->__body}\n";
			} else {
				$strOutput .= "<p>{$this->__body}</p>\n";
			}
		}
		$strOutput .= "</div>\n";
		
		return $strOutput;
	}
	
	public function toJS() {
		return NULL;
	}
	
	public function isValid() {
		return TRUE;
	}
	
	public function getValue() {
		return NULL;
	}
	
	public function hasFields() {
		return FALSE;
	}
	
	public function getFields() {
		return array();
	}
	
}

?>