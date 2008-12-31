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
 * VF_Captcha class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.1
 */
  
require_once('class.vf_element.php');

class VF_Captcha extends VF_Element {
	protected $__width = 200;
	protected $__height = 60;
	protected $__length = 5;
	protected $__textfield;
	
	public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array()) {		
		if (is_null($validationRules)) $validationRules = array();
		if (is_null($errorHandlers)) $errorHandlers = array();
		if (is_null($meta)) $meta = array();
		
		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__label = $label;
		$this->__type = $type;
		$this->__meta = $meta;
		$this->__width = (array_key_exists("width", $meta)) ? $meta["width"] : $this->__width;
		$this->__height = (array_key_exists("height", $meta)) ? $meta["height"] : $this->__height;
		$this->__length = (array_key_exists("length", $meta)) ? $meta["length"] : $this->__length;		
		
		$_SESSION['php_captcha_width'] = $this->__width;
		$_SESSION['php_captcha_height'] = $this->__height;
		$_SESSION['php_captcha_length'] = $this->__length;
		
		$this->__textfield = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);
		
		$this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
	}

	public function toHtml($submitted = FALSE) {		
		$strClass = ($this->__validator->getRequired()) ? "vf__required" : "vf__optional";
		$strOutput = "<div class=\"{$strClass}\">\n";
								
		$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		$strOutput .= "<label>{$strLabel}</label>\n";
		$strOutput .= "<a onclick=\"var cancelClick = false; if (document.images) {  var img = new Image();  var d = new Date();  img.src = this.href + ((this.href.indexOf('?') == -1) ? '?' : '&amp;') + d.getTime();  document.images['{$this->__id}_img'].src = img.src;  cancelClick = true;} return !cancelClick;\" href=\"vf_captcha.php\"><img width=\"{$this->__width}\" height=\"{$this->__height}\" alt=\"Click to view another image\" id=\"{$this->__id}_img\" src=\"vf_captcha.php\"/></a>\n";
		$strOutput .= "</div>\n";
		
		$this->__textfield->setRequiredStyle($this->__requiredstyle);
		$strOutput .= $this->__textfield->toHtml($submitted);
		
		return $strOutput;
	}
	
	public function toJS() {
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : $strCheck;
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";;
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
		$strMaxLengthError = sprintf($this->__validator->getMaxLengthError(), $intMaxLength);
		$strMinLengthError = sprintf($this->__validator->getMinLengthError(), $intMinLength);
		
		return "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '{$this->__validator->getFieldHint()}', '{$this->__validator->getTypeError()}', '{$this->__validator->getRequiredError()}', '{$this->__validator->getHintError()}', '{$strMinLengthError}', '{$strMaxLengthError}');\n";
	}
	
}

?>