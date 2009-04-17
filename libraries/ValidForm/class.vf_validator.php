<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/
 
/**
 * VF_Validator class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version 0.3.0
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
		VFORM_PASSWORD => '/^[-A-Z0-9\.\'"_!@#()$%^&*?]*$/i',
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
