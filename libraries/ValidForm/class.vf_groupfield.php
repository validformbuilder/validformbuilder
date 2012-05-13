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
  
require_once('class.vf_element.php');

/**
 * 
 * GroupField Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
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
		
		$labelMeta = (isset($meta['labelStyle'])) ? array("style" => $meta['labelStyle']) : array();
		if (isset($meta['labelClass'])) $labelMeta["class"] = $meta['labelClass'];
		$this->__labelmeta = $labelMeta;
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
				
		$strOutput = "<label for=\"{$this->__id}\"{$this->__getLabelMetaString()}>\n";
		$strOutput .= "<input type=\"{$this->__type}\" value=\"{$this->__value}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$strChecked} {$this->__getMetaString()} /> {$this->__label}\n";
		$strOutput .= "</label>\n";
		
		return $strOutput;
	}
	
}

?>