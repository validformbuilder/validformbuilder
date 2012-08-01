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
 * Password Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Password extends VF_Element {

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true) {
		$strOutput = "";
		
		if (!$blnSimpleLayout) {
			$blnError = ($submitted && !$this->__validator->validate() && $blnDisplayError) ? TRUE : FALSE;
			
			$strClass = ($this->__validator->getRequired()) ? "vf__required" : "vf__optional";
			$strClass = ($blnError) ? $strClass . " vf__error" : $strClass;
			$strOutput = "<div class=\"{$strClass}\">\n";
			
			if ($blnError) $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError()}</p>";
			
			if ($blnLabel) {
				$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
				if (!empty($this->__label)) $strOutput .= "<label for=\"{$this->__id}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
			}
		} else {
			$strOutput = "<div class=\"vf__multifielditem\">\n";
		}
		
		$strOutput .= "<input type=\"password\" value=\"{$this->__getValue($submitted)}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$this->__getMetaString()} />\n";
		
		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";
		$strOutput .= "</div>\n";
		
		return $strOutput;
	}
	
	public function toJS($strId = null) {
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";;
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
		
		$strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

		if ($this->hasTrigger()) {
			$strOutput .= $this->addTriggerJs($strId);
		}

		return $strOutput;
	}
	
}

?>