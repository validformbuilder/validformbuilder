<?php
/**
 * VF_Condition class
 * A condition object is a set of one or more comparisons.
 *
 * @author Robin van Baalen <robin@neverwoods.com>
 */
class VF_Condition extends ClassDynamic {
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
	public function jsonSerialize() {
		if (get_class($this->__subject) == "VF_GroupField") {
			$identifier = $this->__subject->getId();
		} else {
			$identifier = $this->__subject->getName();
		}

		$arrReturn = array(
			"subject" => $identifier,
			"property" => $this->__property,
			"value" => $this->__value,
			"comparisonType" => $this->__comparisontype,
			"comparisons" => array()
		);

		foreach ($this->__comparisons as $objComparison) {
			array_push($arrReturn["comparisons"], $objComparison->jsonSerialize());
		}

		return $arrReturn;
	}

}

?>