<?php
/**
 * This file is part of ValidFormBuilder.
 *
 * Copyright (c) 2008 Felix Langfeldt
 *
 * ValidFormBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ValidFormBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ValidFormBuilder.  If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * VF_Paragraph class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
  
require_once('class.classdynamic.php');

class VF_Paragraph extends ClassDynamic {
	protected $__header;
	protected $__body;
	
	public function __construct($header = NULL, $body = NULL) {
		$this->__header = $header;
		$this->__body = $body;
	}
	
	public function toHtml($submitted = FALSE) {
		$strOutput = "<div>\n";
		
		if (!empty($this->__header)) $strOutput .= "<h3>{$this->__header}</h3>\n";
		$strOutput .= "<p>{$this->__body}</p>\n";
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