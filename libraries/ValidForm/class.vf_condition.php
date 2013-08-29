<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
*
* Copyright (c) 2009-2013 Neverwoods Internet Technology
* All rights reserved.
*
* This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
*
* @package    ValidForm
* @author     Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
* @copyright  2009-2013 Neverwoods Internet Technology
* @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
* @link       http://code.google.com/p/validformbuilder/
***************************/

require_once('class.vf_classdynamic.php');

/**
 * VF_Condition class
 * A condition object is a set of one or more comparisons.
 *
 * @package ValidForm
 * @author Robin van Baalen <robin@neverwoods.com>
 */
class VF_Condition extends VF_ClassDynamic {
	protected 	$__subject;
	protected	$__property;
	protected 	$__value;
	protected 	$__comparisons = array();
	protected 	$__comparisontype;
	private 	$__conditionProperties = array("visible", "enabled", "required");

	public function __construct ($objField, $strProperty, $blnValue = null, $strComparisonType = VFORM_MATCH_ANY) {
		$strProperty = strtolower($strProperty);

		if (!is_object($objField)) {
			throw new InvalidArgumentException("No valid object passed to VF_Condition.", 1);
		}

		if (!in_array($strProperty, $this->__conditionProperties)) {
			throw new InvalidArgumentException("Invalid type specified in VF_Condition constructor.", 1);
		}

		$this->__subject = $objField;
		$this->__property = $strProperty;
		$this->__comparisontype = $strComparisonType;
		$this->__value = $blnValue;
	}

	/**
	 * Define getters - no need to use the VF_ClassDynamic for that.
	 */
	public function getSubject()
	{
	    return $this->__subject;
	}

	public function getProperty()
	{
	    return $this->__property;
	}

	public function getValue()
	{
	    return $this->__value;
	}

	public function getComparisons()
	{
	    return $this->__comparisons;
	}

	public function getComparisonType()
	{
	    return $this->__comparisontype;
	}

	/**
	 * Add new comparison to Condition
	 * @param VF_Comparison|Array $varComparison Comparison array or VF_Comparison object
	 */
	public function addComparison($varComparison) {
		$objComparison = null;

		if (is_array($varComparison)) {
			$varArguments = (isset($varComparison["subject"])) ? array_values($varComparison) : array_keys($varComparison);

			try {
				$objReflection = new ReflectionClass("VF_Comparison");
				$objComparison = $objReflection->newInstanceArgs($varArguments);
			} catch (Exception $e) {
				throw new Exception("Failed to add Comparison: " . $e->getMessage(), 1);
			}

			if (is_object($objComparison)) {
				array_push($this->__comparisons, $objComparison);
			} else {
				throw new InvalidArgumentException("No valid comparison data supplied in addComparison() method.", 1);
			}
		} else if (is_object($varComparison) && get_class($varComparison) === "VF_Comparison") {
			array_push($this->__comparisons, $varComparison);
		} else {
			throw new InvalidArgumentException("No valid comparison data supplied in addComparison() method.", 1);
		}
	}

	public function isMet($intDynamicPosition = 0) {
		$blnResult = false;

		switch ($this->__comparisontype) {
			default:
			case VFORM_MATCH_ANY:
				/* @var $objComparison VF_Comparison */
				foreach ($this->__comparisons as $objComparison) {
					if ($objComparison->check($intDynamicPosition)) {
						$blnResult = true; // One of the comparisons is true, that's good enough.
						break;
					}
				}

				break;

			case VFORM_MATCH_ALL:
				$blnFailed = false;
				foreach ($this->__comparisons as $objComparison) {
					if (!$objComparison->check($intDynamicPosition)) {
						$blnFailed = true;
						break;
					}
				}

				$blnResult = !$blnFailed;

				break;
		}

		return $blnResult;
	}

	/**
	 * toJson method creates an array representation of the current condition object and all
	 * of it's comparions.
	 *
	 * In the future this class should extend the JsonSerializable interface
	 * (http://php.net/manual/en/class.jsonserializable.php). Since this is only
	 * supported in PHP >= 5.4, we now use our own implementation.
	 *
	 * @return array An array representation of this object and it's comparisons.
	 */
	public function jsonSerialize($intDynamicPosition = null) {
		if (get_class($this->__subject) == "VF_GroupField" || get_class($this->__subject) == "VF_Area") {
			$identifier = $this->__subject->getId();
		} elseif (get_class($this->__subject) == "VF_String") {
		    $identifier = $this->__subject->getMeta("id");
		} else {
			$identifier = $this->__subject->getName();
			if ($intDynamicPosition > 0) $identifier = $identifier . "_" . $intDynamicPosition;
		}

		$arrReturn = array(
			"subject" => $identifier,
			"property" => $this->__property,
			"value" => $this->__value,
			"comparisonType" => $this->__comparisontype,
			"comparisons" => array()
		);

		foreach ($this->__comparisons as $objComparison) {
			array_push($arrReturn["comparisons"], $objComparison->jsonSerialize($intDynamicPosition));
		}

		return $arrReturn;
	}

}

?>