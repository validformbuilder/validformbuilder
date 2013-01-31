<?php

class VF_Condition extends ClassDynamic {
	protected $__field;
	protected $__type;
	protected $__result;
	protected $__comparisons = array();
	protected $__comparisontype;

	private $__conditionTypes = array("disabled", "visible", "required");

	public function __construct ($objField, $strType = null, $blnResult) {
		if (is_object($objField)) {
			$this->__field = $objField;
		} else {
			throw new InvalidArgumentException("No valid object passed to VF_Condition.", 1);
		}

		if (in_array($strType, $this->__conditionTypes)) {
			$this->__type = $strType;
		} else {
			throw new InvalidArgumentException("Invalid type specified in VF_Condition constructor.", 1);
		}

		/**
		 * Set the default Comparison type for this Condition
		 * @var constant
		 */
		$this->__comparisontype = VFORM_MATCH_ANY;

		$this->__result = $blnResult;
	}

	/**
	 * Add new comparison to Condition
	 * @param VF_Comparison|Array $varComparison Comparison array or VF_Comparison object
	 */
	public function addComparison($varComparison) {
		$arrRequiredKeys = VF_Comparison::requiredKeys();
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


	public function checkConditions() {

	}

}

?>