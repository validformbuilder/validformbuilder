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

	public function __construct ($objField, $strProperty = null, $blnValue, $strComparisonType = VFORM_MATCH_ANY) {
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
			$objComparison = new VF_Comparison($varComparison);

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
		switch ($this->__comparisontype) {
			default:
			case VFORM_MATCH_ANY:
				$blnResult = false;
				foreach ($this->__comparisons as $objComparison) {
					if ($objComparison->check($intDynamicPosition)) {
						$blnResult = true; // One of the comparisons is true, that's good enough.
						break;
					}
				}

				break;

			case VFORM_MATCH_ALL:
				$blnResult = false;
				$intComparisonsLength = count($this->__comparisons);
				$intValidComparisons = 0;

				foreach ($this->__comparisons as $objComparison) {
					if ($objComparison->check()) {
						$intValidComparisons++;
					}
				}

				if ($intValidComparisons === $intComparisonsLength) {
					$blnResult = true;
				}

				break;
		}

		// return $blnResult;
		return true;
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
		$arrReturn = array(
			"subject" => $this->__subject->getName(),
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