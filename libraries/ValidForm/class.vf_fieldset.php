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
 * VF_Fieldset class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
  
require_once('class.classdynamic.php');

class VF_Fieldset extends ClassDynamic {
	protected $__header;
	protected $__note;
	protected $__fields = array();
	
	public function __construct($header = NULL, $noteHeader = NULL, $noteBody = NULL) {
		$this->__header = $header;
		
		if (!is_null($noteHeader) || !is_null($noteBody)) {
			$this->__note = new VF_Note($noteHeader, $noteBody);
		}
	}
	
	public function addField($field) {
		array_push($this->__fields, $field);
	}
	
	public function toHtml($submitted = FALSE) {
		$strOutput = "<fieldset>\n";
		if (!empty($this->__header)) $strOutput .= "<legend><span>{$this->__header}</span></legend>\n";
		
		if (is_object($this->__note)) $strOutput .= $this->__note->toHtml();
		
		foreach ($this->__fields as $field) {
			$strOutput .= $field->toHtml($submitted);
		}
		
		$strOutput .= "</fieldset>\n";
	
		return $strOutput;
	}
	
	public function toJS() {
		$strReturn = "";
		
		foreach ($this->__fields as $field) {
			$strReturn .= $field->toJS();
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return $this->__validate();
	}
	
	public function getFields() {
		return $this->__fields;
	}
	
	private function __validate() {
		$blnReturn = TRUE;
		
		foreach ($this->__fields as $field) {
			if (!$field->isValid()) {
				$blnReturn = FALSE;
				break;
			}
		}
		
		return $blnReturn;
	}
	
}

?>