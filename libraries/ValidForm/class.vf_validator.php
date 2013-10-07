<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/

require_once('class.phpcaptcha.php');

/**
 *
 * Validator Class
 *
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.3.3
 *
 */
class VF_Validator {
	static $checks = array(
		VFORM_STRING 		=> '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüýß€0-9%\s*.\'+\/",_!?:;()|& ]*$/i',
		VFORM_TEXT 			=> '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý€ß0-9%\s*.\'+\’\/"_,?#@:;^*!&() ]*$/i',
		VFORM_HTML 			=> '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüý€0-9%\s*.\'\’\/"_,?#@;^*!&() ]*$<:>="/i',
		VFORM_NUMERIC 		=> '/^[-]*[0-9,\.]*$/i',
		VFORM_INTEGER 		=> '/^[0-9]*$/i',
		VFORM_WORD 			=> '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüýß€0-9%_]*$/i',
		VFORM_EMAIL 		=> '/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',
		VFORM_PASSWORD 		=> '/^[-A-Z0-9.\'"_!@#()$%^&*?]*$/i',
		VFORM_SIMPLEURL 	=> '/^[-A-Z0-9]+\.[-A-Z0-9]+/i',
		VFORM_URL 			=> '/^(http(s)?:\/\/)*[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i',
		VFORM_FILE 			=> '/^[-a-zàáâãäåæçèéêëìíîïðñòóôõöøùúûüýß0-9.\':"\\\\_\/ ]*$/i',
		VFORM_BOOLEAN 		=> '/^[on]*$/i',
		VFORM_CAPTCHA 		=> '/^[-a-z]*$/i',
		VFORM_RADIO_LIST 	=> '',
		VFORM_CHECK_LIST 	=> '',
		VFORM_SELECT_LIST 	=> '',
		VFORM_PARAGRAPH 	=> '',
		VFORM_CURRENCY 		=> '',
		VFORM_HIDDEN 		=> '',
		VFORM_DATE 			=> '/^(\d{2}[-|\/|\\\\|\.]\d{2}[-|\/|\\\\|\.]\d{4})$/i'
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
						if (is_array($value)) {
							$arrValues = $value;
							$blnSub = true;
							foreach ($arrValues as $value) {
								$blnSub = preg_match(self::$checks[$checkType], $value);
								if (!$blnSub) {
									//*** At least 1 value is not valid, skip the rest and return false;
									exit;
								}
							}

							$blnReturn = $blnSub;
						} else {
							$blnReturn = preg_match(self::$checks[$checkType], $value);
						}
				}
			}
		} else {
			if (empty($checkType)) {
				$blnReturn = TRUE; // No custom validation set.
			} else {
				$blnReturn = @preg_match($checkType, $value); // Use custom validation
			}
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
