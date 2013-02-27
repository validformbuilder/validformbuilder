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
 * Captcha Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.3
 *
 */
class VF_Captcha extends VF_Element {
	protected $__width = 200;
	protected $__height = 60;
	protected $__length = 5;
	protected $__path = "/";
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
		$this->__path = (array_key_exists("path", $meta)) ? $meta["path"] : $this->__path;

		$_SESSION['php_captcha_width'] = $this->__width;
		$_SESSION['php_captcha_height'] = $this->__height;
		$_SESSION['php_captcha_length'] = $this->__length;

		$this->__textfield = new VF_Text($name, $type, $label, $validationRules, $errorHandlers, $meta);

		// $this->__validator = new VF_FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
		$this->__validator = new VF_FieldValidator($this, $validationRules, $errorHandlers);
	}

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		$strClass = ($this->__validator->getRequired()) ? "vf__required" : "vf__optional";
		$strOutput = "<div class=\"{$strClass}\">\n";

		$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
		if (!empty($this->__label)) $strOutput .= "<label>{$strLabel}</label>\n";
		$strOutput .= "<a onclick=\"var cancelClick = false; if (document.images) {  var img = new Image();  var d = new Date();  img.src = this.href + ((this.href.indexOf('?') == -1) ? '?' : '&amp;') + d.getTime();  document.images['{$this->__id}_img'].src = img.src;  cancelClick = true;} return !cancelClick;\" href=\"{$this->__path}vf_captcha.php\"><img width=\"{$this->__width}\" height=\"{$this->__height}\" alt=\"Click to view another image\" id=\"{$this->__id}_img\" src=\"{$this->__path}vf_captcha.php\"/></a>\n";
		$strOutput .= "</div>\n";

		$this->__textfield->setRequiredStyle($this->__requiredstyle);
		$strOutput .= $this->__textfield->toHtml($submitted);

		return $strOutput;
	}

	public function toJS() {
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";;
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

		return "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";
	}

}

?>