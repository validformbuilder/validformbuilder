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
 * Hidden Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.1
 *
 */
class VF_Hidden extends VF_Element {
	protected $__dynamiccounter;

	public function __construct($name, $type, $meta = array()) {
		if (is_null($meta)) $meta = array();
		
		$this->__id = (strpos($name, "[]") !== FALSE) ? $this->getRandomId($name) : $name;
		$this->__name = $name;
		$this->__type = $type;
		$this->__meta = $meta;
		$this->__tip = (array_key_exists("tip", $meta)) ? $meta["tip"] : NULL;
		$this->__hint = (array_key_exists("hint", $meta)) ? $meta["hint"] : NULL;
		$this->__default = (array_key_exists("default", $meta)) ? $meta["default"] : NULL;
		$this->__dynamiccounter = (array_key_exists("dynamicCounter", $meta)) ? $meta["dynamicCounter"] : false;

		$this->__validator = new VF_FieldValidator($name, $type, array(), array(), $this->__hint);		
	}
	
	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true) {
		$strOutput = "";
						
		$strOutput .= "<input type=\"hidden\" value=\"{$this->__getValue($submitted)}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$this->__getMetaString()} />\n";
				
		return $strOutput;
	}
	
	public function toJS() {
		return "";
	}
	
	public function hasFields() {
		return FALSE;
	}

	public function isDynamicCounter() {
		return $this->__dynamiccounter;
	}

	public function isValid() {
		$blnReturn = false;
		$intDynamicCount = ($this->isDynamicCounter()) ? $this->__validator->getValue() : 0;

		for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
			$blnReturn = $this->__validator->validate($intCount);
			
			if (!$blnReturn) {
				break;
			}
		}

		return $blnReturn;
	}
	
}

?>