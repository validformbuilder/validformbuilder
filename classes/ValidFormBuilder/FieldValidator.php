<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@cattlea.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@cattlea.com>
 * @copyright 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
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
 * @author Robin van Baalen <robin@cattlea.com>
 * @version Release: 3.0.0
 *
 * @method Base getField() getField() Returns the value of `$__field`
 * @method void setField() setField(Base $value) Overwrites the value of `$__field`
 * @method integer getType() getType() Returns the value of `$__type`
 * @method void setType() setType(integer $value) Overwrites the value of `$__type`
 * @method string getFieldName() getFieldName() Returns the value of `$__fieldname`
 * @method void setFieldName() setFieldName(string $value) Overwrites the value of `$__fieldname`
 * @method string getFieldHint() getFieldHint() Returns the value of `$__fieldhint`
 * @method void setFieldHint() setFieldHint(string $value) Overwrites the value of `$__fieldhint`
 * @method integer getMinLength() getMinLength() Returns the value of `$__minlength`
 * @method void setMinLength() setMinLength(integer $value) Overwrites the value of `$__minlength`
 * @method integer getMaxLength() getMaxLength() Returns the value of `$__minlength`
 * @method void setMaxLength() setMaxLength(integer $value) Overwrites the value of `$__minlength`
 * @method Element getMatchWith() getMatchWith() Returns the value of `$__matchwith`
 * @method void setMatchWith() setMatchWith(Element $value) Overwrites the value of `$__matchwith`
 * @method integer getMaxFiles() getMaxFiles() Returns the value of `$__maxfiles`
 * @method void setMaxFiles() setMaxFiles(integer $value) Overwrites the value of `$__maxfiles`
 * @method integer getMaxSize() getMaxSize() Returns the value of `$__maxsize`
 * @method void setMaxSize() setMaxSize(integer $value) Overwrites the value of `$__maxsize`
 * @method array getFileTypes() getFileTypes() Returns the value of `$__filetypes`
 * @method void setFileTypes() setFileTypes(array $value) Overwrites the value of `$__filetypes`
 * @method string getValidation() getValidation() Returns the value of `$__validation`
 * @method void setValidation() setValidation(string $value) Overwrites the value of `$__validation`
 * @method boolean getDefaultRequired() getDefaultRequired() Returns the value of `$__defaultRequired`
 * @method void setDefaultRequired() setDefaultRequired(boolean $value) Overwrites the value of `$__defaultRequired`
 * @method string getMinLengthError() getMinLengthError() Returns the value of `$__minlengtherror`
 * @method void setMinLengthError() setMinLengthError(string $value) Overwrites the value of `$__minlengtherror`
 * @method string getMaxLengthError() getMaxLengthError() Returns the value of `$__maxlengtherror`
 * @method void setMaxLengthError() setMaxLengthError(string $value) Overwrites the value of `$__maxlengtherror`
 * @method string getMatchWithError() getMatchWithError() Returns the value of `$__matchwitherror`
 * @method void setMatchWithError() setMatchWithError(string $value) Overwrites the value of `$__matchwitherror`
 * @method string getRequiredError() getRequiredError() Returns the value of `$__requirederror`
 * @method void setRequiredError() setRequiredError(string $value) Overwrites the value of `$__requirederror`
 * @method string getTypeError() getTypeError() Returns the value of `$__typeerror`
 * @method void setTypeError() setTypeError(string $value) Overwrites the value of `$__typeerror`
 * @method string getHintError() getHintError() Returns the value of `$__hinterror`
 * @method void setHintError() setHintError(string $value) Overwrites the value of `$__hinterror`
 */
class FieldValidator extends ClassDynamic
{
    /**
     * Field object
     * @var \ValidFormBuilder\Base
     */
    protected $__field;

    /**
     * Validation type
     * @var integer
     */
    protected $__type;

    /**
     * Fieldname
     * @var string
     */
    protected $__fieldname; // Not the same as __field->getName()

    /**
     * Field hint
     * @var string
     */
    protected $__fieldhint;

    /**
     * Valid values
     * @var array
     */
    protected $__validvalues = array();

    /**
     * Validation rule min length
     * @var integer
     */
    protected $__minlength;

    /**
     * Validation rule max length
     * @var integer
     */
    protected $__maxlength;

    /**
     * Valdiation rule matchWith
     * @var Element
     */
    protected $__matchwith;

    /**
     * Validation rule required
     * @var boolean
     */
    protected $__required = false;

    /**
     * Validation rule max files
     * @var integer
     */
    protected $__maxfiles = 1;

    /**
     * Validation rule max size
     * @var integer
     */
    protected $__maxsize = 3000;

    /**
     * Validation rule filetypes
     * @var array
     */
    protected $__filetypes;

    /**
     * Validation regular expression
     * @var string
     */
    protected $__validation;

    /**
     * Default required state
     * @var boolean
     */
    protected $__defaultRequired = false;

    /**
     * Min length error
     * @var string
     */
    protected $__minlengtherror = "The input is too short. The minimum is %s characters.";
    /**
     * Max length error
     * @var string
     */
    protected $__maxlengtherror = "The input is too long. The maximum is %s characters.";
    /**
     * Match with error
     * @var string
     */
    protected $__matchwitherror = "The values do not match.";
    /**
     * Required error
     * @var string
     */
    protected $__requirederror = "This field is required.";
    /**
     * Type error
     * @var string
     */
    protected $__typeerror;
    /**
     * Overwrite errors
     * @var array
     */
    protected $__overrideerrors = array();
    /**
     * Max files error
     * @var string
     */
    protected $__maxfileserror = "Too many files selected. The maximum is %s files.";
    /**
     * Max size error
     * @var string
     */
    protected $__maxsizeerror = "The filesize is too big. The maximum is %s KB.";
    /**
     * File type error
     * @var string
     */
    protected $__filetypeerror = "Invalid file types selected. Only types of %s are permitted.";
    /**
     * Hint error
     * @var string
     */
    protected $__hinterror = "The value is the hint value. Enter your own value.";
    /**
     * Errors
     * @var array
     */
    protected $__errors = array();
    /**
     * Presanatize
     * @var array
     */
    protected $__presanatize = array();

    /**
     * Construct new validation object
     *
     * @param Element $objField
     * @param array $arrValidationRules
     * @param array $arrErrorHandlers
     * @param boolean $preSanitize
     */
    public function __construct(Element $objField, Array $arrValidationRules = array(), Array $arrErrorHandlers = array(), $preSanitize = true)
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

        //sanitize value previous to validation
        $this->__presanatize = $preSanitize;
    }

    /**
     * Get the validated value
     *
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
                if (is_array($varValidValue) && isset($varValidValue[$intDynamicPosition])) {
                    $varValidValue = $varValidValue[$intDynamicPosition];
                }

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

            /*** return sanitized value **/
            if ($this->__presanatize) {
                $varReturn = $this->preSanitize($varReturn);
            }
        }

        return $varReturn;
    }

    /**
     * Pre Sanitize element before being validated
     * @param string|array|null field value.  $value
     * @return string
     */
    public function preSanitize($value)
    {
        return trim($value);
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
            if (($this->__required && $intDynamicPosition == 0) || !!$this->__field->getMeta('dynamicRemoveLabel', false)) {
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
            if (!empty($this->__validation)) {
                $blnValidType = Validator::validate($this->__validation, $value);
            } else {
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
     * @return string
     */
    public function getCheck()
    {
        if (!empty($this->__validation)) {
            $strReturn = $this->__validation;
        } else {
            $strReturn = Validator::getCheck($this->__field->getType());
        }

        return $strReturn;
    }

    /**
     * Check if an error has occured
     * @param integer $intDynamicPosition
     * @return boolean
     */
    private function __hasError($intDynamicPosition = 0)
    {
        return (isset($this->__errors[$intDynamicPosition]) && ! empty($this->__errors[$intDynamicPosition]));
    }
}
