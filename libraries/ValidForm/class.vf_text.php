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
 * Text Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Text extends VF_Element {

	public function toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true) {
		$strOutput = "";

		if ($this->__dynamic) {
			$intDynamicCount = $this->getDynamicCount();
			for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
			}
		} else {
			$strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
		}

		return $strOutput;
	}

	public function __toHtml($submitted = FALSE, $blnSimpleLayout = FALSE, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0) {
		$strOutput 	= "";

		$strName 	= ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
		$strId 		= ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;

		$varValue = $this->__getValue($submitted, $intCount);

		$blnError = ($submitted && !$this->__validator->validate($intCount) && $blnDisplayErrors) ? TRUE : FALSE;

		if (!$blnSimpleLayout) {
			//*** We asume that all dynamic fields greater than 0 are never required.
			if ($this->__validator->getRequired() && $intCount == 0) {
				$this->setMeta("class", "vf__required");
			} else {
				$this->setMeta("class", "vf__optional");
			}

			//*** Set custom meta.
			if ($blnError) $this->setMeta("class", "vf__error");
			if (!$blnLabel) $this->setMeta("class", "vf__nolabel");

			if (!empty($this->__hint) && ($varValue == $this->__hint)) {
			    $this->setMeta("class", "vf__hint");
			}

			// Call this right before __getMetaString();
			$this->setConditionalMeta();

			$strOutput = "<div{$this->__getMetaString()}>\n";

			if ($blnError) {
				$strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
			}

			if ($blnLabel) {
				$strLabel = (!empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
				if (!empty($this->__label)) $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
			}
		} else {
		    if (!empty($this->__hint) && ($varValue == $this->__hint)) {
		        $this->setMeta("class", "vf__hint");
		    }

			if ($blnError) $this->setMeta("class", "vf__error");
			$this->setMeta("class", "vf__multifielditem");

			// Call this right before __getMetaString();
			$this->setConditionalMeta();

			$strOutput = "<div{$this->__getMetaString()}>\n";
		}

		//*** Add max-length attribute to the meta array. This is being read by the getMetaString method.
		if ($this->__validator->getMaxLength() > 0) {
			$this->setFieldMeta("maxlength", $this->__validator->getMaxLength());
		}

		$strOutput .= "<input type=\"text\" value=\"{$varValue}\" name=\"{$strName}\" id=\"{$strId}\"{$this->__getFieldMetaString()} />\n";

		if (!empty($this->__tip)) $strOutput .= "<small class=\"vf__tip\">{$this->__tip}</small>\n";

		$strOutput .= "</div>\n";

		if (!$blnSimpleLayout
			&& $this->__dynamic
			&& !empty($this->__dynamicLabel)
			&& ($intCount == $this->getDynamicCount())
		) {
			$strOutput .= "<div class=\"vf__dynamic vf__cf\"><a href=\"#\" data-target-id=\"{$this->__id}\" data-target-name=\"{$this->__name}\">{$this->__dynamicLabel}</a></div>\n";
		}

		return $strOutput;
	}

	public function toJS($blnParentIsDynamic = FALSE) {
		$strOutput = "";
		$strCheck = $this->__validator->getCheck();
		$strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
		$strRequired = ($this->__validator->getRequired()) ? "true" : "false";
		$intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
		$intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

		if ($this->__dynamic || $blnParentIsDynamic) {
			$intDynamicCount = $this->getDynamicCount($blnParentIsDynamic);
			for($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
				$strId 		= ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
				$strName 	= ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;

				//*** We asume that all dynamic fields greater than 0 are never required.
				if ($intDynamicCount > 0) $strRequired = "false";

				$strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

				//*** MatchWith logic per dynamic field.
				$strOutput .= $this->matchWithToJs($intCount);

				//*** Render the condition logic per dynamic field.
				$strOutput .= $this->conditionsToJs($intCount);
			}
		} else {
			$strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

			//*** MatchWith logic.
			$strOutput .= $this->matchWithToJs();

			//*** Condition logic.
			$strOutput .= $this->conditionsToJs();
		}

		return $strOutput;
	}

}

?>