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
 * VF_GroupField class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
  
require_once('class.vf_element.php');

class VF_GroupField extends VF_Element {
	protected $__id;
	protected $__name;
	protected $__label;
	protected $__value;
	protected $__type;
	protected $__checked;
	protected $__meta;
	
	public function __construct($id, $name, $type, $label, $value, $checked = FALSE, $meta = array()) {
		$this->__id = $id;
		$this->__name = $name;
		$this->__type = $type;
		$this->__label = $label;
		$this->__value = $value;
		$this->__checked = $checked;
		$this->__meta = $meta;
	}
	
	public function toHtml($value = NULL, $submitted = FALSE) {
		if (is_array($value)) {
			foreach ($value as $valueItem) {
				if ($valueItem == $this->__value) {
					$strChecked = " checked=\"checked\"";
					break;
				} else {
					$strChecked = "";
				}
			}
		} else {
			$strChecked = ($this->__checked && is_null($value) && !$submitted) ? " checked=\"checked\"" : "";
			$strChecked = ($value == $this->__value) ? " checked=\"checked\"" : $strChecked;
		}
				
		$strOutput = "<label for=\"{$this->__id}\">\n";
		$strOutput .= "<input type=\"{$this->__type}\" value=\"{$this->__value}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$strChecked} {$this->__getMetaString()} /> {$this->__label}\n";
		$strOutput .= "</label>\n";
		
		return $strOutput;
	}
	
}

?>