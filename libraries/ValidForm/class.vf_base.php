<?php
/**
 * ValidForm Base class.
 * All ValidForm classes share this base logic.
 */
class VF_Base extends ClassDynamic {
	protected $__id;
	protected $__name;
	protected $__parent;
	protected $__conditions = array();
	protected $__meta;
	protected $__reservedmeta = array("parent", "data", "dynamicCounter", "tip", "hint", "default", "width", "height", "length", "start", "end", "path", "labelStyle", "labelClass", "labelRange", "valueRange", "dynamic", "dynamicLabel", "matchWith");

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
	 * for example 'getCondition()->isMet()'.
	 * 
	 * @param  String $strType 		Condition type e.g. 'required', 'visibile' and 'disabled'
	 * @return VF_Condition|null    The found condition or null if no condition is found.
	 */
	public function getCondition($strProperty) {
		$objReturn = null;

		$objConditions = $this->getConditions();
		foreach ($objConditions as $objCondition) {
			if ($objCondition->getProperty() === strtolower($strProperty)) {
				$objReturn = $objCondition;
				break;
			}
		}
		
		if (is_null($objReturn) && is_object($this->__parent)) {
			//*** Find condition in parent.
			$objReturn = $this->__parent->getCondition($strProperty);
		}

		return $objReturn;
	}


	/**
	 * Check if the current fields contains a condition object
	 * @param  String  $strProperty Condition type (e.g. 'required', 'disabled', 'visible' etc.)
	 * @return boolean          True if element has condition object set, false if not
	 */
	public function hasCondition($strProperty) {
		$blnReturn = false;

		foreach ($this->__conditions as $objCondition) {
			if ($objCondition->getProperty() === strtolower($strProperty)) {
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
		foreach ($this->__conditions as $objCondition) {
			$blnResult = $objCondition->isMet();

			switch ($objCondition->getProperty()) {
				case "visible":
					// This can be applied on all sorts of subjects.
					if ($objCondition->isMet() && !$objCondition->getValue()) {
						$this->setMeta("style", "display: none;");
					}
					break;

				case "enabled":
					// This can only be applied on all subjects except for Paragraphs
					if (get_class($objCondition->getSubject()) !== "VF_Paragraph") {

					}
					break;

				case "required":

					break;
			}
		}
	}

// 	public function isVisible() {
// 		$blnReturn = true;
// 		$objCondition = $this->getCondition("visible");

// 		if (!is_null($objCondition) && $objCondition->isMet()) {
// 			$blnReturn = $objCondition->getValue();
// 		}

// 		return $blnReturn;
// 	}

// 	public function isEnabled() {
// 		return true;
// 	}

// 	public function isRequired() {
// 		return false;
// 	}

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
			
			//*** Define delimiter per meta property.
			switch ($property) {
				case "style":
					$strDelimiter = ";";
					break;
					
				default: 
					$strDelimiter = " ";
			}
			
			//*** Add the value to the property string.
			$arrMeta = explode($strDelimiter, $varMeta);
			$varMeta = implode($strDelimiter, array_push($arrMeta, $value));
			
			$this->__meta[$property] = $varMeta;
		}
	}

	/**
	 * Get meta property.
	 * @param  string $property Property to get from internal meta array.
	 * @return string           Property value or empty string of none is set.
	 */
	public function getMeta($property) {
		return (isset($this->__meta[$property]) && !is_null($this->__meta[$property])) ? $this->__meta[$property] : "";
	}

	public function getName() {
		$strName = parent::getName();
		if (empty($strName)) {
			$strName = $this->__name = $this->__generateName();
		}

		return $strName;
	}

	public function toJS() {
		$strOutput = "";

		if ($this->hasConditions() && (count($this->getConditions() > 0))) {
			foreach ($this->getConditions() as $objCondition) {
				$strOutput .= "objForm.addCondition(" . json_encode($objCondition->jsonSerialize()) . ");\n";
			}
		}

		return $strOutput;
	}

	protected function __generateName() {
		return strtolower(get_class($this)) . "_" . mt_rand();
	}

	protected function __getMetaString() {
		$strOutput = "";

		foreach ($this->__meta as $key => $value) {
			if (!in_array($key, $this->__reservedmeta)) {
				$strOutput .= " {$key}=\"{$value}\"";
			}
		}

		return $strOutput;
	}

	private function __checkConditionProperty($strProp) {
		$blnReturn = false;

		if ($this->hasCondition($strProp) && $this->getCondition($strProp)->isMet()) {
			$blnReturn = $this->getCondition($strProp)->getValue();
		}
	}
}
?>