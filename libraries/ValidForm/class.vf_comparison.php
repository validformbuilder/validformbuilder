<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
*
* Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
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

require_once('class.vf_classdynamic.php');

/**
 * VF_Comparison class
 * A comparison object is part of a condition and represents a single value comparison.
 *
 * @package ValidForm
 * @author Robin van Baalen <robin@neverwoods.com>
 */
class VF_Comparison extends VF_ClassDynamic {
	protected $__subject;
	protected $__comparison;
	protected $__value;
	private static $__requiredKeys = array(
		"subject",
		"comparison",
		"value"
	);

	public function __construct($objSubject, $varComparison, $varValue = null) {
		if (($varComparison !== VFORM_COMPARISON_EMPTY && $varComparison !== VFORM_COMPARISON_NOT_EMPTY) && is_null($varValue)) {
			// If the comparison is not 'empty' or 'not empty', a 'value' key is required in the 'arrData' argument.
			throw new InvalidArgumentException("Value is required in VF_Comparison construct when using comparison '" . $varComparison . "'.", 1);
		}

		// If the subject is a required field, we cannot set the VFORM_COMPARISON_EMPTY check
		if ($objSubject->getValidator()->getRequired() && $varComparison === VFORM_COMPARISON_EMPTY) {
			throw new Exception("Cannot add 'empty' comparison to required field '{$objSubject->getName()}'.", 1);
		}

		// It's all good, populate the local properties.
		$this->__subject = $objSubject;
		$this->__comparison = $varComparison;
		$this->__value = $varValue;
	}

	/**
	 * Check this comparison
	 * @param	Number	Dynamic position of the subject to check
	 * @return 	Boolean True if Comparison meets requirements, false if not.
	 */
	public function check($intDynamicPosition = 0) {
		$blnReturn = false;

		if ($this->__subject instanceof VF_Element) {
			// Any element based on VF_Element
			$strValue = $this->__subject->__getValue(true, $intDynamicPosition);
			$strValue = (is_null($strValue)) ? $strValue = $this->__subject->getValue($intDynamicPosition) : $strValue;

			if (!is_null($strValue)) {
			    //*** Get the postioned value if a dynamic field is part of a comparison.
			    if (is_array($strValue) && isset($strValue[$intDynamicPosition])) {
			        $strValue = $strValue[$intDynamicPosition];
			    }

			    $blnReturn = $this->__verify($strValue);
			}
		} else {
			throw new Exception("Invalid subject supplied in VF_Comparison. Class " . get_class($this->__subject) . " given. Expecting instance of VF_Element." , 1);
		}

		return $blnReturn;
	}

	public static function requiredKeys() {
		return self::$__requiredKeys;
	}

	public function jsonSerialize($intDynamicPosition = null) {
		if (get_class($this->__subject) == "VF_GroupField") {
			$identifier = $this->__subject->getId();
		} else {
			$identifier = $this->__subject->getName();
			if ($intDynamicPosition > 0) $identifier = $identifier . "_" . $intDynamicPosition;
		}

		$arrReturn = array(
			"subject" => $identifier, // For now, this ony applies to fields and should apply to both fields, area's, fieldsets and paragraphs.
			"comparison" => $this->__comparison,
			"value" => $this->__value
		);

		return $arrReturn;
	}

	/**
	 * Verify this comparison against the actual value
	 * @param  String $strValue The actual value that is submitted
	 * @return Boolean           True if comparison succeeded, false if not.
	 */
	private function __verify($strValue) {
		$blnReturn 			= false;
		$strLowerValue 		= strtolower($strValue);
		$strCompareAgainst 	= strtolower($this->__value);

		switch ($this->__comparison) {
			case VFORM_COMPARISON_EQUAL:
				$blnReturn = ($strLowerValue == $strCompareAgainst);
				break;
			case VFORM_COMPARISON_NOT_EQUAL:
				$blnReturn = ($strLowerValue != $strCompareAgainst);
				break;
			case VFORM_COMPARISON_LESS_THAN:
				$blnReturn = ($strValue < $this->__value);
				break;
			case VFORM_COMPARISON_GREATER_THAN:
				$blnReturn = ($strValue > $this->__value);
				break;
			case VFORM_COMPARISON_LESS_THAN_OR_EQUAL:
				$blnReturn = ($strValue <= $this->__value);
				break;
			case VFORM_COMPARISON_GREATER_THAN_OR_EQUAL:
				$blnReturn = ($strValue >= $this->__value);
				break;
			case VFORM_COMPARISON_EMPTY:
				$blnReturn = empty($strValue);
				break;
			case VFORM_COMPARISON_NOT_EMPTY:
				$blnReturn = !empty($strValue);
				break;
			case VFORM_COMPARISON_STARTS_WITH:
				// strpos is faster than substr and way faster than preg_match.
				$blnReturn = (strpos($strLowerValue, $strCompareAgainst) === 0);
				break;
			case VFORM_COMPARISON_ENDS_WITH:
				$blnReturn = (substr($strLowerValue, -strlen($strCompareAgainst)) === $strCompareAgainst);
				break;
			case VFORM_COMPARISON_CONTAINS:
				$blnReturn = (strpos($strLowerValue, $strCompareAgainst) !== false);
				break;
			case VFORM_COMPARISON_REGEX:
				$blnReturn = preg_match($this->__value, $strValue);
				break;
		}

		return $blnReturn;
	}

}
?>