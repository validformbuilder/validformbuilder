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
 * String Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.3
 *
 */
class VF_String extends VF_Base {
	protected $__id;
	protected $__body;
	protected $__dynamiccounter = false;

	public function __construct($bodyString, $meta = array()) {
		$this->__body = $bodyString;
		$this->__meta = $meta;
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE) {
		return $this->__toHtml($submitted, $blnSimpleLayout);
	}

	public function __toHtml($submitted = FALSE, $blnSimpleLayout = FALSE) {
		$strOutput = "";

		if (!$blnSimpleLayout) {
		    // Call this right before __getMetaString();
		    $this->setConditionalMeta();

			$strOutput = str_replace("[[metaString]]", $this->__getMetaString(), $this->__body);
		} else {
		    $this->setMeta("class", "vf__multifielditem");
			$strOutput = "<div " . $this->__getMetaString() . "><span>{$this->__body}</span></div>\n";
		}

		return $strOutput;
	}

	public function toJS($blnParentIsDynamic = FALSE) {
		$strOutput = "";

		if ($this->getMeta("id")) {
		    $strId = $this->getMeta("id");

    		$strOutput = "objForm.addElement('{$strId}', '{$strId}');\n";

    		//*** Condition logic.
    		$strOutput .= $this->conditionsToJs();
		}

		return $strOutput;
	}

	public function isValid() {
		return TRUE;
	}

	public function hasFields() {
		return FALSE;
	}

	public function getValue() {
		return;
	}

	public function getValidator() {
		return null;
	}

	public function getName() {
		return;
	}

	public function getData() {
		return;
	}

	public function isDynamic() {
		return false;
	}

}

?>