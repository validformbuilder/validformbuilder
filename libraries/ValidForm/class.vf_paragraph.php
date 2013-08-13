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
 * Paragraph Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Paragraph extends VF_Base {
	protected $__header;
	protected $__body;

	public function __construct($header = NULL, $body = NULL, $meta = array()) {
		$this->__header = $header;
		$this->__body = $body;
		$this->__meta = $meta;

		$this->__initializeMeta();

		$this->setMeta("id", $this->getName());
	}

	public function __toHtml() {
		return $this->toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true);
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayError = true) {
		// Call this before __getMetaString();
		$this->setConditionalMeta();

		$this->setMeta("class", "vf__paragraph");

		$strOutput = "<div{$this->__getMetaString()}>\n";

		// Add header if not empty.
		if (!empty($this->__header)) $strOutput .= "<h3{$this->__getLabelMetaString()}>{$this->__header}</h3>\n";

		if (!empty($this->__body)) {
			if (preg_match("/<p.*?>/", $this->__body) > 0) {
				$strOutput .= "{$this->__body}\n";
			} else {
				$strOutput .= "<p{$this->__getFieldMetaString()}>{$this->__body}</p>\n";
			}
		}

		$strOutput .= "</div>\n";

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

	public function isDynamic() {
		return FALSE;
	}

	public function getValue() {
		return NULL;
	}

	public function hasFields() {
		return FALSE;
	}

	public function getFields() {
		return array();
	}
}

?>