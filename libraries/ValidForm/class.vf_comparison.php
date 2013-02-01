<?php
class VF_Comparison extends ClassDynamic {
	protected $__subject;
	protected $__comparison;
	protected $__value;
	private static $__requiredKeys = array("subject", "comparison", "value");

	public function __construct(Array $arrData) {
		if ((isset($arrData["comparison"]) && isset($arrData["subject"]))) {
			if (($arrData["comparison"] !== VFORM_COMPARISON_EMPTY && $arrData["comparison"] !== VFORM_COMPARISON_NOT_EMPTY) && !isset($arrData["value"])) {
				// If the comparison is not 'empty' or 'not empty', a 'value' key is required in the 'arrData' argument.
				throw new InvalidArgumentException("'Value' key is required in VF_Comparison construct.", 1);
			}

			foreach ($arrData as $strKey => $strValue) {
				if (property_exists($this, strtolower("__" . $strKey))) {
					$strMethod = "set" . ucfirst(strtolower($strKey));
					$this->$strMethod($strValue);
				}
			}

			// If the subject is a required field, we cannot set the VFORM_COMPARISON_EMPTY check
			if ($this->__subject->getValidator()->getRequired() && $this->__comparison === VFORM_COMPARISON_EMPTY) {
				throw new Exception("Cannot add 'empty' comparison to required field '{$this->__subject->getName()}'.", 1);
			}
		} else {
			throw new InvalidArgumentException("Invalid array supplied in VF_Comparison.", 1);
		}
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
			$strValue = $this->__subject->getValue($intDynamicPosition);
			if (!is_null($strValue)) {
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

	/**
	 * Verify this comparison against the actual value
	 * @param  String $strValue The actual value that is submitted
	 * @return Boolean           True if comparison succeeded, false if not.
	 */
	private function __verify($strValue) {
		$blnReturn = false;

		switch ($this->__comparison) {
			case VFORM_COMPARISON_EQUAL:
				$blnReturn = ($strValue == $this->__value);
				break;
			case VFORM_COMPARISON_NOT_EQUAL:
				$blnReturn = ($strValue != $this->__value);
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
				$blnReturn = (strpos($strValue, $this->__value) === 0);
				break;
			case VFORM_COMPARISON_ENDS_WITH:
				$blnReturn = (substr($strValue, -strlen($this->__value)) === $this->__value);
				break;
			case VFORM_COMPARISON_CONTAINS:
				$blnReturn = (strpos($strValue, $this->__value) !== false);
				break;
		}

		return $blnReturn;
	}

	private function isValidData () {
		$blnReturn = false;

		foreach ($arrData as $strKey => $strValue) {
			if (!array_key_exists(strtolower($strKey), self::requiredKeys())) {
				$blnReturn = false;
				break;
			}
		}

		return $blnReturn;
	}

}
?>