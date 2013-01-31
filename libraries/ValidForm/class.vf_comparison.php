<?php
class VF_Comparison extends ClassDynamic {
	protected $__subject;
	protected $__comparison;
	protected $__value;

	public function __construct(Array $arrData) {
		if (array_key_exists("subject", self::requiredKeys())
			&& array_key_exists("comparison", self::requiredKeys())
			&& array_key_exists("value", self::requiredKeys())
		) {
			foreach ($arrData as $strKey => $strValue) {
				if (property_exists($this, strtolower("__" . $strKey))) {
					$strMethod = "set" . ucfirst(strtolower($strKey));
					$this->$strMethod($strValue);
				}
			}
		}
	}

	public static function requiredKeys() {
		return array("subject", "comparison", "value");
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