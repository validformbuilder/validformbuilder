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

require_once('class.vf_base.php');

/**
 *
 * SelectGroup Class
 *
 * @package ValidForm
 * @author Robin van Baalen
 * @version Release: 0.2.1
 *
 */
class VF_SelectGroup extends VF_Base {
	protected $__label;
	protected $__options;

	public function __construct($label) {
		$this->__label = $label;
		$this->__options = new VF_Collection();
	}

	public function toHtml($value = NULL) {
		$strOutput = "<optgroup label=\"{$this->__label}\">\n";
		foreach ($this->__options as $option) {
			$strOutput .= $option->toHtml($value);
		}
		$strOutput .= "</optgroup>\n";

		return $strOutput;
	}

	public function addField($label, $value, $selected = FALSE) {
		$objOption = new VF_SelectOption($label, $value, $selected);
		$objOption->setMeta("parent", $this, true);

		$this->__options->addObject($objOption);

		return $objOption;
	}

}

?>