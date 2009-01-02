<?php
/**
 * This file is part of ValidFormBuilder.
 *
 * Copyright (c) 2008 Felix Langfeldt
 *
 * ValidFormBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ValidFormBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ValidFormBuilder.  If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * VF_Validator class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.3
 */
 
require_once('class.phpcaptcha.php');

class VF_Validator {
	static $checks = array(
		VFORM_STRING => '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý€0-9\s*\.\'\/",_()|& ]*$/i',
		VFORM_TEXT => '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý€0-9\s*\.\'\/"_,?#@^*!&() ]*$/i',
		VFORM_NUMERIC => '/^[0-9,\.]*$/i',
		VFORM_INTEGER => '/^[0-9]*$/i',
		VFORM_WORD => '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý€0-9_]*$/i',
		VFORM_EMAIL => '/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',
		VFORM_PASSWORD => '/^[-A-Z0-9\.\'"_!@#$%^&*?]*$/i',
		VFORM_SIMPLEURL => '/^[-A-Z0-9]+\.[-A-Z0-9]+/i',
		VFORM_FILE => '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý0-9\.\':"\\_\/ ]*$/i',
		VFORM_BOOLEAN => '/^[on]*$/i',
		VFORM_CAPTCHA => '/^[-a-z]*$/i',
		VFORM_RADIO_LIST => '',
		VFORM_CHECK_LIST => '',
		VFORM_SELECT_LIST => '',
		VFORM_PARAGRAPH => '',
		VFORM_CURRENCY => '',
		VFORM_DATE => '/^(\d{2}\/\d{2}\/\d{4})$/i',
	);
	
	public static function validate($checkType, $value) {
		$blnReturn = FALSE;		

		if (array_key_exists($checkType, self::$checks)) {
			if (empty(self::$checks[$checkType])) {
				$blnReturn = TRUE;
			} else {
				switch ($checkType) {
					case VFORM_CAPTCHA:
						$blnReturn = PhpCaptcha::Validate(ValidForm::get($value));
						break;
					default:
						$blnReturn = preg_match(self::$checks[$checkType], $value);
				}
			}
		} else {
			$blnReturn = preg_match($checkType, $value);
		}

		return $blnReturn;
	}
	
	public static function getCheck($checkType) {
		$strReturn = "";
		
		if (array_key_exists($checkType, self::$checks)) {
			$strReturn = self::$checks[$checkType];
		}
		
		return $strReturn;
	}
}

?>
