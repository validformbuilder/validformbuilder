<?php
/**
 * ValidForm Base class.
 * All ValidForm classes share this base logic.
 */
class VF_Base extends ClassDynamic {
	protected $__id;
	protected $__conditions = array();
	protected $__meta;
	protected $__reservedmeta = array("data", "dynamicCounter", "tip", "hint", "default", "width", "height", "length", "start", "end", "path", "labelStyle", "labelClass", "labelRange", "valueRange", "dynamic", "dynamicLabel", "matchWith");

	/**
	 * Add a new condition to the current field
	 * @param [type] $strType           [description]
	 * @param [type] $blnValue          [description]
	 * @param [type] $arrComparisons    [description]
	 * @param [type] $intComparisonType [description]
	 */
	public function addCondition($strType, $blnValue, $arrComparisons, $intComparisonType = VFORM_MATCH_ANY) {
		if ($this->hasCondition($strType)) {
			// Get an existing condition if it's already there.
			$objCondition = $this->getCondition($strType);
		} else {
			// Add a new one if this condition type doesn't exist yet.
			$objCondition = new VF_Condition($this, $strType, $blnValue, $intComparisonType);
		}

		if (is_array($arrComparisons) && count($arrComparisons) > 0) {
			foreach ($arrComparisons as $arrComparison) {
				if (is_array($arrComparison)) {
					try {
						$objCondition->addComparison($arrComparison);
					} catch (InvalidArgumentException $e) {
						throw new Exception("Could not set condition: " . $e->getMessage(), 1);
					}
				} else {
					throw new InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
				}
			}

			array_push($this->__conditions, $objCondition);
		} else {
			throw new InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
		}
	}

	/**
	 * Get element's VF_Condition object
	 * Note: When chaining methods, always use hasCondition() first before chaining
	 * for example 'getCondition()->getResult()'.
	 * @param  String $strType 		Condition type e.g. 'required', 'visibile' and 'disabled'
	 * @return VF_Condition|null    The found condition or null if no condition is found.
	 */
	public function getCondition($strType) {
		$objConditions = $this->getConditions();
		$objCondition = null;

		foreach ($objConditions as $objCondition) {
			if ($objCondition->getType() === strtolower($strType)) {
				break;
			}
		}

		return $objCondition;
	}


	/**
	 * Check if the current fields contains a condition object
	 * @param  String  $strType Condition type (e.g. 'required', 'disabled', 'visible' etc.)
	 * @return boolean          True if element has condition object set, false if not
	 */
	public function hasCondition($strType) {
		$blnReturn = false;

		foreach ($this->__conditions as $objCondition) {
			if ($objCondition->getType() === strtolower($strType)) {
				$blnReturn = true;
				break;
			}
		}

		return $blnReturn;
	}

	public function hasConditions() {
		return (count($this->__conditions) > 0);
	}

	public function setConditionalStyling() {
		foreach ($this->getConditions() as $objCondition) {
			$blnResult = $objCondition->getResult();

			switch ($objCondition->getProperty()) {
				case "visible":
					// This can be applied on all sorts of subjects.
					if ($blnResult && !$objCondition->getValue()) {
						$this->setMeta("style", "display: none;");
					}
					break;

				case "disabled":
					// This can only be applied on all subjects except for Paragraphs
					if (get_class($objCondition->getSubject()) !== "VF_Paragraph") {

					}
					break;

				case "required":

					break;
			}
		}
	}

	/**
	 * Set meta property.
	 * @param string  	$property     Property name.
	 * @param mixed  	$value        Property value.
	 * @param boolean 	$blnOverwrite Overwrite previous property value.
	 */
	public function setMeta($property, $value, $blnOverwrite = false) {
		if ($blnOverwrite) {
			$this->__meta[$property] = $value;
		} else {
			$varMeta = (isset($this->__meta[$property])) ? $this->__meta[$property] : "";
			$varMeta .= " " . $value;
			$this->__meta[$property] = $varMeta;
			$this->__meta[$property] = ltrim($this->__meta[$property]);
		}
	}

	/**
	 * Get meta property.
	 * @param  string $property Property to get from internal meta array.
	 * @return string           Property value or empty string of none is set.
	 */
	public function getMeta($property) {
		return (isset($this->__meta[$property]) && !empty($this->__meta["property"])) ? $this->__meta[$property] : "";
	}
}
?>