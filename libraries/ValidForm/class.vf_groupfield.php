<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@neverwoods.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package    ValidForm
 * @author     Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright  2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://validformbuilder.org
 ***************************/

require_once('class.vf_element.php');

/**
 * GroupField Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 */
class VF_GroupField extends VF_Element {
	protected $__label;
	protected $__value;
	protected $__checked;

	public function __construct($id, $name, $type, $label, $value, $checked = FALSE, $meta = array()) {
		parent::__construct($name, $type, $label, array(), array(), $meta);

		$this->__id = $id;
		$this->__value = $value;
		$this->__checked = $checked;
	}

	public function toHtml($value = NULL, $submitted = FALSE) {
		$strChecked = "";

		if (is_array($value)) {
			foreach ($value as $valueItem) {
				if ($valueItem == $this->__value) {
					$this->setFieldMeta("checked", "checked");
					// $strChecked = " checked=\"checked\"";
					break;
				} else {
					$this->setFieldMeta("checked", null, true); // Remove 'checked'
					// $strChecked = "";
				}
			}
		} else {
			if ($this->__checked && is_null($value) && !$submitted) $this->setFieldMeta("checked", "checked");
			if ($value == $this->__value) $this->setFieldMeta("checked", "checked");
		}

		//*** Convert the Element type to HTML type.
		/* TODO: Refactor to typeToHtmlType method and implement in all element classes. */
		$type = "";
		switch ($this->__type) {
			case VFORM_RADIO_LIST:
				$type = "radio";
				break;
			case VFORM_CHECK_LIST:
				$type = "checkbox";
				break;
		}
		
		$strOutput = "<label for=\"{$this->__id}\"{$this->__getLabelMetaString()}>\n";
		$strOutput .= "<input type=\"{$type}\" value=\"{$this->__value}\" name=\"{$this->__name}\" id=\"{$this->__id}\"{$this->__getFieldMetaString()}/> {$this->__label}\n";
		$strOutput .= "</label>\n";

		return $strOutput;
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