<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2025 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@stylr.nl>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @copyright 2009-2025 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */
namespace ValidFormBuilder;

/**
 * Validator Class
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
 */
class Validator
{
    /**
     * Static array of validation types
     * @var array
     */
    public static $checks = array(
        ValidForm::VFORM_STRING => '/^[-a-zàáâãäåāæçèéêẽëēìíîïĩīðñòóôõöōøùúûũüūýß€0-9%\s*.\'+\/",_–!?#@:;()|& =]*$/iu',
        ValidForm::VFORM_TEXT => '/^[-a-zàáâãäåāæçèéêẽëēìíîïĩīðñòóôõöōøùúûũüūýß€0-9%\s*.\'+\’\/"_–,?#@:;^*!&() =]*$/iu',
        ValidForm::VFORM_HTML => '/^[-a-zàáâãäåāæçèéêẽëēìíîïĩīðñòóôõöōøùúûũüūýß€0-9%\s*.\'\’\/"_–,?#@;^*!&() <:>=]*$/iu',
        ValidForm::VFORM_NUMERIC => '/^[-]*[0-9,\.]*$/i',
        ValidForm::VFORM_INTEGER => '/^[0-9]*$/i',
        ValidForm::VFORM_WORD => '/^[-a-zàáâãäåāæçèéêẽëēìíîïĩīðñòóôõöōøùúûũüūýß€0-9%_–]*$/iu',
        ValidForm::VFORM_EMAIL => '/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i',
        ValidForm::VFORM_PASSWORD => '',
        ValidForm::VFORM_SIMPLEURL => '/^[-A-Z0-9]+\.[-A-Z0-9]+/i',
        ValidForm::VFORM_URL => '/^(http(s)?:\/\/)*[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i',
        ValidForm::VFORM_FILE => '/^[-a-zàáâãäåāæçèéêẽëēìíîïĩīðñòóôõöōøùúûũüūýß0-9.\':"\\\\_–\/ ]*$/iu',
        ValidForm::VFORM_BOOLEAN => '/^[on]*$/i',
        ValidForm::VFORM_RADIO_LIST => '',
        ValidForm::VFORM_CHECK_LIST => '',
        ValidForm::VFORM_SELECT_LIST => '',
        ValidForm::VFORM_PARAGRAPH => '',
        ValidForm::VFORM_CURRENCY => '',
        ValidForm::VFORM_HIDDEN => '',
        ValidForm::VFORM_DATE => '/^(\d{2}[-|\/|\\\\|\.]\d{2}[-|\/|\\\\|\.]\d{4})$/i'
    );

    /**
     * Validate input against regular expression
     *
     * @param integer $checkType The type to check for
     * @param string $value The value to validate
     * @return boolean True if valid, false if not.
     */
    public static function validate($checkType, $value)
    {
        if (array_key_exists($checkType, self::$checks)) {
            if (empty(self::$checks[$checkType])) {
                $blnReturn = true;
            } else {
                if (is_array($value)) {
                    $arrValues = $value;
                    $blnSub = true;
                    foreach ($arrValues as $value) {
                        $blnSub = preg_match(self::$checks[$checkType], $value);
                        if (! $blnSub) {
                            // *** At least 1 value is not valid, skip the rest and return false;
                            exit();
                        }
                    }

                    $blnReturn = $blnSub;
                } else {
                    $blnReturn = preg_match(self::$checks[$checkType], $value);
                }
            }
        } else {
            if (empty($checkType)) {
                $blnReturn = true; // No custom validation set.
            } else {
                $blnReturn = @preg_match($checkType, $value); // Use custom validation
            }
        }

        return $blnReturn;
    }

    /**
     * Get the regular expression that is used by the given field type
     * @param integer $checkType Field type
     * @return string The matching regular expression
     */
    public static function getCheck($checkType)
    {
        $strReturn = "";

        if (array_key_exists($checkType, self::$checks)) {
            $strReturn = self::$checks[$checkType];
        }

        return $strReturn;
    }
}
