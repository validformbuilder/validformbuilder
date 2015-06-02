<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@neverwoods.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */
namespace ValidFormBuilder;

/**
 * FieldValidator Class
 *
 * This class handles all the validation logic
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 *
 * @internal
 */
class FieldValidator extends ClassDynamic
{
    /**
     * Field object
     * @internal
     * @var \ValidFormBuilder\Base
     */
    protected $__field;

    /**
     * Validation type
     * @internal
     * @var integer
     */
    protected $__type;

    /**
     * Fieldname
     * @internal
     * @var string
     */
    protected $__fieldname; // Not the same as __field->getName()

    /**
     * Field hint
     * @internal
     * @var string
     */
    protected $__fieldhint;

    /**
     * Valid values
     * @internal
     * @var array
     */
    protected $__validvalues = array();

    /**
     * Validation rule min length
     * @internal
     * @var integer
     */
    protected $__minlength;

    /**
     * Validation rule max length
     * @internal
     * @var integer
     */
    protected $__maxlength;

    /**
     * Valdiation rule matchWith
     * @internal
     * @var \ValidFormBuilder\Base
     */
    protected $__matchwith;

    /**
     * Validation rule required
     * @internal
     * @var boolean
     */
    protected $__required = false;

    /**
     * Validation rule max files
     * @internal
     * @var integer
     */
    protected $__maxfiles = 1;

    /**
     * Validation rule max size
     * @internal
     * @var integer
     */
    protected $__maxsize = 3000;

    /**
     * Validation rule filetypes
     * @internal
     * @var array
     */
    protected $__filetypes;

    /**
     * Validation regular expression
     * @internal
     * @var string
     */
    protected $__validation;

    /**
     * Default required state
     * @internal
     * @var boolean
     */
    protected $__defaultRequired = false;

    /**
     * Min length error
     * @internal
     * @var string
     */
    protected $__minlengtherror = "The input is too short. The minimum is %s characters.";
    /**
     * Max length error
     * @internal
     * @var string
     */
    protected $__maxlengtherror = "The input is too long. The maximum is %s characters.";
    /**
     * Match with error
     * @internal
     * @var string
     */
    protected $__matchwitherror = "The values do not match.";
    /**
     * Required error
     * @internal
     * @var string
     */
    protected $__requirederror = "This field is required.";
    /**
     * Type error
     * @internal
     * @var string
     */
    protected $__typeerror;
    /**
     * Overwrite errors
     * @internal
     * @var array
     */
    protected $__overrideerrors = array();
    /**
     * Max files error
     * @internal
     * @var string
     */
    protected $__maxfileserror = "Too many files selected. The maximum is %s files.";
    /**
     * Max size error
     * @internal
     * @var string
     */
    protected $__maxsizeerror = "The filesize is too big. The maximum is %s KB.";
    /**
     * File type error
     * @internal
     * @var string
     */
    protected $__filetypeerror = "Invalid file types selected. Only types of %s are permitted.";
    /**
     * Hint error
     * @internal
     * @var string
     */
    protected $__hinterror = "The value is the hint value. Enter your own value.";
    /**
     * Errors
     * @internal
     * @var array
     */
    protected $__errors = array();

    /**
     * Construct new validation object
     *
     * @internal
     * @param Element $objField
     * @param array $arrValidationRules
     * @param array $arrErrorHandlers
     */
    public function __construct(Element $objField, Array $arrValidationRules = array(), Array $arrErrorHandlers = array())
    {
        foreach ($arrValidationRules as $key => $value) {
            $property = strtolower("__" . $key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        foreach ($arrErrorHandlers as $key => $value) {
            $property = strtolower("__" . $key . "error");
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        $this->__field = $objField;
        $this->__type = $objField->getType();
        $this->__fieldname = str_replace("[]", "", $objField->getName());
        $this->__fieldhint = $objField->getHint();

        // Store the default required state in a seperate property.
        // This way, we're able to reset back to default settings at any given time.
        $this->__defaultRequired = $this->__required;
    }

    /**
     * Get the validated value
     *
     * @internal
     * @param integer $intDynamicPosition
     * @return array|string
     */
    public function getValidValue($intDynamicPosition = 0)
    {
        $varReturn = null;

        if (isset($this->__validvalues[$intDynamicPosition])) {
            $varReturn = $this->__validvalues[$intDynamicPosition];
        }

        return $varReturn;
    }

    /**
     * Get the value to validate from either the global request variable or the cached __validvalues array.
     *
     * @internal
     * @param integer $intDynamicPosition Using the intDynamicPosition parameter, you can get the specific value
     * of a dynamic field.
     * @return string|array|null Returns the submitted field value. If no sumitted value is set,
     * return value is the cached valid value. If no cached value is set, return value is the default value. If no
     * default value is set, return value is null. When field type is `ValidForm::VFORM_FILE` and a file is submitted,
     * the return value is the `$_FILES[fieldname]` array.
     */
    public function getValue($intDynamicPosition = 0)
    {
        $varReturn = null;

        if (isset($this->__overrideerrors[$intDynamicPosition]) && empty($this->__overrideerrors[$intDynamicPosition])) {
            $varReturn = null;
        } else {
            $strFieldName = ($intDynamicPosition > 0) ? $this->__fieldname . "_" . $intDynamicPosition : $this->__fieldname;

            //if ($this->__type !== ValidForm::VFORM_FILE) {
                // Default value
                $varValidValue = $this->__field->getDefault();

                // Get cached value if set
                if (isset($this->__validvalues[$intDynamicPosition])) {
                    $varValidValue = $this->__validvalues[$intDynamicPosition];
                }

                // Overwrite cached value with value from REQUEST array if available
                if (ValidForm::getIsSet($strFieldName)) {
                    $varValue = ValidForm::get($strFieldName);

                    if (is_array($varValue)) {
                        $varReturn = [];

                        foreach ($varValue as $key => $value) {
                            $varReturn[$key] = $value; // NEVER return unsanitized output
                        }
                    } else {
                        $varReturn = $varValue; // NEVER return unsanitized output
                    }
                } else {
                    $varReturn = $varValidValue;
                }
            //}
            // *** Not ready for implementation yet.
            // else {
            // if (isset($_FILES[$strFieldName]) && isset($_FILES[$strFieldName])) {
            // $varReturn = $_FILES[$strFieldName];
            // }
            // }
        }

        return $varReturn;
    }

    /**
     * Set required state
     * @param boolean $blnValue
     */
    public function setRequired($blnValue)
    {
        // Convert whatever is given into a real boolean by using !!
        $this->__required = ! ! $blnValue;
    }

    /**
     * Get required state
     * @param boolean $blnDefault
     * @return boolean
     */
    public function getRequired($blnDefault = false)
    {
        return (!!$blnDefault) ? $this->__defaultRequired : $this->__required;
    }

    /**
     * The most important function of ValidForm Builder library.
     *
     * This function handles all the server-side field validation logic.
     * @internal
     * @param integer $intDynamicPosition Using the intDynamicPosition parameter, you can validate a specific dynamic
     * field, if necessary.
     * @return boolean True if the current field validates, false if not.
     */
    public function validate($intDynamicPosition = 0)
    {
        // Reset the internal errors array
        $this->__errors = array();

        // *** Get the value to validate from either the global request variable or the cached __validvalues array.
        $value = $this->getValue($intDynamicPosition);

        // *** Get required an visible states from condition and overwrite values for validation purposes
        $objCondition = $this->__field->getConditionRecursive("required");
        if (is_object($objCondition)) {
            if ($objCondition->isMet($intDynamicPosition)) {
                $this->__required = $objCondition->getValue();
            } else {
                $this->__required = ! $objCondition->getValue();
            }
        }

        $objCondition = $this->__field->getConditionRecursive("enabled");
        if (is_object($objCondition)) {
            if ($objCondition->isMet($intDynamicPosition)) {
                $this->__required = ($objCondition->getValue()) ? $this->__required : false;
            } else {
                $this->__required = ($objCondition->getValue()) ? false : $this->__required;
            }
        }

        $objCondition = $this->__field->getConditionRecursive("visible");
        if (is_object($objCondition)) {
            if ($objCondition->isMet($intDynamicPosition)) {
                $this->__required = ($objCondition->getValue()) ? $this->__required : false;
            } else {
                $this->__required = ($objCondition->getValue()) ? false : $this->__required;
            }
        }

        // Check if parent element is an area.
        // If so, check if it's an active area that is selected
        $objParent = $this->__field->getMeta("parent", null);
        if (! is_null($objParent) && get_class($objParent) === "ValidFormBuilder\\Area") {
            if ($objParent->isActive() && ! $objParent->getValue($intDynamicPosition)) {
                $this->__required = false;
            }
        }

        // *** Check "required" option.
        if (is_array($value)) {
            $blnEmpty = true;
            $intCount = 0;

            foreach ($value as $valueItem) {
                if (strlen($valueItem) > 0) {
                    $blnEmpty = false;
                    break;
                }

                $intCount ++;
            }

            if ($blnEmpty) {
                if ($this->__required) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = $this->__requirederror;
                } else {
                    $this->__validvalues[$intDynamicPosition] = "";
                    return true;
                }
            }
        } elseif (strlen($value) == 0) {
            if ($this->__required && $intDynamicPosition == 0) {
                // *** Only the first dynamic field has a required check. We asume by design that "real" dynamic fields are not required.
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = $this->__requirederror;
            } else {
                unset($this->__validvalues[$intDynamicPosition]);

                if (empty($this->__matchwith)) {
                    return true;
                }
            }
        }

        // *** Check if value is_null and not required. No other checks needed.
        if (! $this->__required && is_null($value)) {
            return true;
        }

        // *** Check if value is hint value.
        if (! $this->__hasError($intDynamicPosition)) {
            $strHint = $this->__field->getHint();
            if (! empty($strHint) && ! is_array($value)) {
                if ($strHint == $value) {
                    if ($this->__required) {
                        // *** If required then it's an error.
                        unset($this->__validvalues[$intDynamicPosition]);
                        $this->__errors[$intDynamicPosition] = $this->__hinterror;
                    } else {
                        // *** If optional then empty value and return true.
                        unset($this->__validvalues[$intDynamicPosition]);
                        return true;
                    }
                }
            }
        }

        // *** Check minimum input length.
        if (! $this->__hasError($intDynamicPosition)) {
            if ($this->__minlength > 0 && is_array($value)) {
                if (count($value) < $this->__minlength) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
                }
            } elseif ($this->__minlength > 0 && strlen($value) < $this->__minlength) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
            }
        }

        // *** Check maximum input length.
        if (! $this->__hasError($intDynamicPosition)) {
            if ($this->__maxlength > 0 && is_array($value)) {
                if (count($value) > $this->__maxlength) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = sprintf($this->__maxlengtherror, $this->__maxlength);
                }
            } elseif ($this->__maxlength > 0 && strlen($value) > $this->__maxlength) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = sprintf($this->__maxlengtherror, $this->__maxlength);
            }
        }

        // *** Check matching values.
        if (! $this->__hasError($intDynamicPosition)) {
            if (! empty($this->__matchwith)) {
                $matchValue = $this->__matchwith->getValue();
                if (empty($matchValue)) {
                    $matchValue = null;
                }

                if (empty($value)) {
                    $value = null;
                }

                if ($matchValue !== $value) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = $this->__matchwitherror;
                } elseif (is_null($value)) {
                    return true;
                }
            }
        }

        // *** Check specific types.
        if (! $this->__hasError($intDynamicPosition)) {
            switch ($this->__field->getType()) {
                case ValidForm::VFORM_CUSTOM:
                case ValidForm::VFORM_CUSTOM_TEXT:
                    $blnValidType = Validator::validate($this->__validation, $value);
                    break;
                default:
                    $blnValidType = Validator::validate($this->__field->getType(), $value);
            }

            if (! $blnValidType) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = $this->__typeerror;
            } else {
                if (is_array($value) && is_array($value[0])) {
                    //*** Set the value directly when the value is a nested array.
                    $this->__validvalues = $value;
                } else {
                    $this->__validvalues[$intDynamicPosition] = $value;
                }
            }
        }

        // *** Override error.
        if (isset($this->__overrideerrors[$intDynamicPosition]) && ! empty($this->__overrideerrors[$intDynamicPosition])) {
            unset($this->__validvalues[$intDynamicPosition]);
            $this->__errors[$intDynamicPosition] = $this->__overrideerrors[$intDynamicPosition];
        }

        return (!isset($this->__validvalues[$intDynamicPosition])) ? false : true;
    }

    /**
     * Set custom error on a field
     *
     * Use this to set a custom error on a field
     *
     * @internal
     * @param string $strError Custom error message
     * @param integer $intDynamicPosition
     */
    public function setError($strError, $intDynamicPosition = 0)
    {
        $this->__overrideerrors[$intDynamicPosition] = $strError;
    }

    /**
     * Get error message
     *
     * @internal
     * @param integer $intDynamicPosition
     * @return string
     */
    public function getError($intDynamicPosition = 0)
    {
        $strReturn = "";

        if (isset($this->__errors[$intDynamicPosition]) && !empty($this->__errors[$intDynamicPosition])) {
            $strReturn = $this->__errors[$intDynamicPosition];
        }

        return $strReturn;
    }

    /**
     * Get the validation rule (regular expression)
     * @internal
     * @return string
     */
    public function getCheck()
    {
        $strReturn = "";

        switch ($this->__field->getType()) {
            case ValidForm::VFORM_CUSTOM:
            case ValidForm::VFORM_CUSTOM_TEXT:
                $strReturn = $this->__validation;
                break;
            default:
                $strReturn = Validator::getCheck($this->__field->getType());
        }

        return $strReturn;
    }

    /**
     * Check if an error has occured
     * @internal
     * @param integer $intDynamicPosition
     * @return boolean
     */
    private function __hasError($intDynamicPosition = 0)
    {
        return (isset($this->__errors[$intDynamicPosition]) && ! empty($this->__errors[$intDynamicPosition]));
    }
}
