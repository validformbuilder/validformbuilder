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
 * SelectOption Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.3
 *
 */
class VF_SelectOption extends VF_Element {
	protected $__label;
	protected $__value;
	protected $__selected;

	public function __construct($label, $value, $selected = FALSE, $meta = array()) {
		if (is_null($meta)) $meta = array();

		$this->__label = $label;
		$this->__value = $value;
		$this->__selected = $selected;
		$this->__meta = $meta;
	}

	public function toHtml($value = null) {
	    $strSelected = "";
	    if ($this->__selected && is_null($value)) {
	        $strSelected = " selected=\"selected\"";
	    }

	    if ($value == $this->__value) {
	        $strSelected = " selected=\"selected\"";
	    }

		$strOutput = "<option value=\"{$this->__value}\"{$strSelected} {$this->__getMetaString()}>{$this->__label}</option>\n";

		return $strOutput;
	}

	public function getValue() {
		return $this->__value;
	}

	public function __getValue($submitted = false, $intCount = 0) {
		$varReturn = parent::__getValue($submitted, $intCount);

		if (is_null($varReturn)) {
			$varReturn = $this->__value;
		}

		return $varReturn;
	}

}

?>