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
 * VF_SelectOption class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
  
require_once('class.classdynamic.php');

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