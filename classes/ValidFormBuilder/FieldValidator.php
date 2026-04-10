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
 * FieldValidator Class
 *
 * This class handles all the validation logic
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
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
 * @method integer getMaxLength() getMaxLength() Returns the value of `$__maxlength`
 * @method void setMaxLength() setMaxLength(integer $value) Overwrites the value of `$__maxlength`
 * @method integer getMinValue() getMinValue() Returns the value of `$__minvalue`
 * @method void setMinValue() setMinValue(float $value) Overwrites the value of `$__minvalue`
 * @method integer getMaxValue() getMaxValue() Returns the value of `$__maxvalue`
 * @method void setMaxValue() setMaxValue(float $value) Overwrites the value of `$__maxvalue`
 * @method integer getOnlyListItems() getOnlyListItems() Returns the value of `$__onlylistitems`
 * @method void setOnlyListItems() setOnlyListItems(bool $value) Overwrites the value of `$__onlylistitems`
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
 * @method string getMinValueError() getMinValueError() Returns the value of `$__minvalueerror`
 * @method void setMinValueError() setMinValueError(string $value) Overwrites the value of `$__minvalueerror`
 * @method string getMaxValueError() getMaxValueError() Returns the value of `$__maxvalueerror`
 * @method void setMaxValueError() setMaxValueError(string $value) Overwrites the value of `$__maxvalueerror`
 * @method string getOnlyListItemsError() getOnlyListItemsError() Returns the value of `$__onlylisterror`
 * @method void setOnlyListItemsError() setOnlyListItemsError(string $value) Overwrites the value of `$__onlylistitemserror`
 * @method string getMatchWithError() getMatchWithError() Returns the value of `$__onlylistitemserror`
 * @method void setMatchWithError() setMatchWithError(string $value) Overwrites the value of `$__matchwitherror`
 * @method string getRequiredError() getRequiredError() Returns the value of `$__requirederror`
 * @method void setRequiredError() setRequiredError(string $value) Overwrites the value of `$__requirederror`
 * @method string getTypeError() getTypeError() Returns the value of `$__typeerror`
 * @method void setTypeError() setTypeError(string $value) Overwrites the value of `$__typeerror`
 * @method string getHintError() getHintError() Returns the value of `$__hinterror`
 * @method void setHintError() setHintError(string $value) Overwrites the value of `$__hinterror`
 * @method array|null getSanitisers() getSanitisers() Returns the value of `$__sanitisers`
 * @method void setSanitisers() setSanitisers(array $value) Overwrites the value of `$__sanitisers`
 * @method array|null getExternalValidation() getExternalValidation() Returns the value of `$__externalvalidation`
 * @method void setExternalValidation() setExternalValidation(array $value) Overwrites the value of `$__externalvalidation`
 * @method string getExternalValidationError() getExternalValidationError() Returns the value of `$__externalvalidationerror`
 * @method void setExternalValidationError() setExternalValidationError(string $value) Overwrites the value of `$__externalvalidationerror`
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
     * Validation rule minimum input size, when dealing with floats or integers
     * @var int|float
     */
    protected $__minvalue;

    /**
     * Validation rule maximum input size, when dealing with floats or integers
     * @var int|float
     */
    protected $__maxvalue;

    /**
     * Valdiation rule matchWith
     * @var Element
     */
    protected $__matchwith;

    /**
     * Valdiation rule onlyListItems
     * @var Element
     */
    protected $__onlylistitems = false;

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
     * Min size error
     * @var string
     */
    protected $__minvalueerror = "The input value is too small. The minimum is %s.";
    /**
     * Max size error
     * @var string
     */
    protected $__maxvalueerror = "The input value is too large. The maximum is %s.";
    /**
     * Only list items error
     * @var string
     */
    protected $__onlylistitemserror = "The input is not in the list of possible values.";
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
     * Sanitization rules.
     * @var array|null
     */
    protected $__sanitisers = null;

    /**
     * External validation check.
     * @var array|null
     */
    protected $__externalvalidation = null;

    /**
     * External validation error
     * @var string
     */
    protected $__externalvalidationerror = "The external validation error.";

    /**
     * Construct new validation object
     *
     * @param Element $objField
     * @param array $arrValidationRules
     * @param array $arrErrorHandlers
     * @param ?array $arrSanitizationRules
     */
    public function __construct(
        Element $objField,
        array $arrValidationRules = array(),
        array $arrErrorHandlers = array(),
        ?array $arrSanitizationRules = null
    ) {
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
        $this->__sanitisers = $arrSanitizationRules;

        // Store the default required state in a seperate property.
        // This way, we're able to reset back to default settings at any given time.
        $this->__defaultRequired = $this->__required;
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

        $sanitizedValue = $this->preSanitize($value);

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
        if (is_array($sanitizedValue)) {
            $blnEmpty = true;
            $intCount = 0;

            foreach ($sanitizedValue as $valueItem) {
                if (strlen((string)$valueItem) > 0) {
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

                    if ($this->checkOverrideErrors($intDynamicPosition)) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        } elseif (strlen((string)$sanitizedValue) == 0) {
            if (($this->__required && $intDynamicPosition == 0) || !!$this->__field->getMeta('dynamicRemoveLabel', false)) {
                // *** Only the first dynamic field has a required check. We asume by design that "real" dynamic fields are not required.
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = $this->__requirederror;
            } else {
                unset($this->__validvalues[$intDynamicPosition]);

                if (empty($this->__matchwith)) {
                    if ($this->checkOverrideErrors($intDynamicPosition)) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }

        // *** Check if value is_null and not required. No other checks needed.
        if (! $this->__required && is_null($sanitizedValue)) {
            if ($this->checkOverrideErrors($intDynamicPosition)) {
                return false;
            } else {
                return true;
            }
        }

        // *** Check if value is hint value.
        if (! $this->__hasError($intDynamicPosition)) {
            $strHint = $this->__field->getHint();
            if (! empty($strHint) && ! is_array($sanitizedValue)) {
                if ($strHint == $sanitizedValue) {
                    if ($this->__required) {
                        // *** If required then it's an error.
                        unset($this->__validvalues[$intDynamicPosition]);
                        $this->__errors[$intDynamicPosition] = $this->__hinterror;
                    } else {
                        // *** If optional then empty value and return true.
                        unset($this->__validvalues[$intDynamicPosition]);

                        if ($this->checkOverrideErrors($intDynamicPosition)) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
        }

        // *** Check minimum input length.
        if (! $this->__hasError($intDynamicPosition)) {
            if ($this->__minlength > 0 && is_array($sanitizedValue)) {
                if (count($sanitizedValue) < $this->__minlength) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
                }
            } elseif ($this->__minlength > 0 && strlen((string)$sanitizedValue) < $this->__minlength) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = sprintf($this->__minlengtherror, $this->__minlength);
            }
        }

        // *** Check maximum input length.
        if (! $this->__hasError($intDynamicPosition)) {
            if ($this->__maxlength > 0 && is_array($sanitizedValue)) {
                if (count($sanitizedValue) > $this->__maxlength) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = sprintf($this->__maxlengtherror, $this->__maxlength);
                }
            } elseif ($this->__maxlength > 0 && strlen((string)$sanitizedValue) > $this->__maxlength) {
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

                if (empty($sanitizedValue)) {
                    $sanitizedValue = null;
                }

                if ($matchValue !== $sanitizedValue) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = $this->__matchwitherror;
                } elseif (is_null($sanitizedValue)) {
                    if ($this->checkOverrideErrors($intDynamicPosition)) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }

        // *** Check specific types.
        if (! $this->__hasError($intDynamicPosition)) {
            if (!empty($this->__validation)) {
                $blnValidType = Validator::validate($this->__validation, $sanitizedValue);
            } else {
                $blnValidType = Validator::validate($this->__field->getType(), $sanitizedValue);
            }

            if (! $blnValidType) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = $this->__typeerror;
            } else {
                if (is_array($sanitizedValue) && is_array($sanitizedValue[0])) {
                    //*** Set the value directly when the value is a nested array.
                    $this->__validvalues = $sanitizedValue;
                } else {
                    $this->__validvalues[$intDynamicPosition] = $sanitizedValue;
                }
            }
        }

        // *** Check list type values.
        if (! $this->__hasError($intDynamicPosition)) {
            $blnFieldHasFixedValues = in_array(
                $this->__field->getType(),
                [ValidForm::VFORM_SELECT_LIST, ValidForm::VFORM_RADIO_LIST, ValidForm::VFORM_CHECK_LIST]
            );

            if ($blnFieldHasFixedValues && $this->__onlylistitems) {
                $arrFixedValues = [];

                switch ($this->__field->getType()) {
                    case ValidForm::VFORM_SELECT_LIST:
                        $arrFixedValueFields = $this->__field->getOptions();

                        /** @var SelectOption $objValueField */
                        foreach ($arrFixedValueFields as $objValueField) {
                            $arrFixedValues[] = $objValueField->getValue($intDynamicPosition);
                        }

                        break;
                    default:
                        $arrFixedValueFields = $this->__field->getFields();

                        /** @var GroupField $objValueField */
                        foreach ($arrFixedValueFields as $objValueField) {
                            $arrFixedValues[] = $objValueField->__getValue(false, $intDynamicPosition);
                        }
                }

                if (! in_array($sanitizedValue, $arrFixedValues)) {
                    unset($this->__validvalues[$intDynamicPosition]);
                    $this->__errors[$intDynamicPosition] = $this->__onlylistitemserror;
                }
            }
        }

        // *** Check minimum input value.
        if (! $this->__hasError($intDynamicPosition)) {
            if (isset($this->__minvalue) && self::toFloat($sanitizedValue) < $this->__minvalue) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = sprintf($this->__minvalueerror, $this->__minvalue);
            }
        }

        // *** Check maximum input value.
        if (! $this->__hasError($intDynamicPosition)) {
            if (isset($this->__maxvalue) && self::toFloat($sanitizedValue) > $this->__maxvalue) {
                unset($this->__validvalues[$intDynamicPosition]);
                $this->__errors[$intDynamicPosition] = sprintf($this->__maxvalueerror, $this->__maxvalue);
            }
        }

        // *** Check external validation.
        if (! $this->__hasError($intDynamicPosition)) {
            if (is_array($this->__externalvalidation) && isset($this->__externalvalidation['php'])) {
                $callback = $this->__externalvalidation['php'][0] ?? null;
                $arrArguments = $this->__externalvalidation['php'][1] ?? [];

                array_unshift($arrArguments, $sanitizedValue);

                if (is_callable($callback)) {
                    $blnResult = call_user_func_array($callback, $arrArguments);

                    if (!$blnResult) {
                        unset($this->__validvalues[$intDynamicPosition]);
                        $this->__errors[$intDynamicPosition] = sprintf($this->__externalvalidationerror, $this->__externalvalidation);
                    }
                }
            }
        }

        // *** Override error.
        if ($this->checkOverrideErrors($intDynamicPosition)) {
            unset($this->__validvalues[$intDynamicPosition]);
        }

        return (!isset($this->__validvalues[$intDynamicPosition])) ? false : true;
    }

    protected function checkOverrideErrors($intDynamicPosition)
    {
        $blnReturn = false;

        if (isset($this->__overrideerrors[$intDynamicPosition]) && !empty($this->__overrideerrors[$intDynamicPosition])) {
            $this->__errors[$intDynamicPosition] = $this->__overrideerrors[$intDynamicPosition];

            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Sanitize a value according to preset rules.
     *
     * @param $varValue
     * @param null|array $arrSanitisers
     * @return string
     */
    public function sanitize($varValue, $arrSanitisers = null)
    {
        //*** Use either the provided value or the locally preset value for sanitization.
        $arrSanitisers = (!is_null($arrSanitisers)) ? $arrSanitisers : $this->__sanitisers;

        if (is_array($arrSanitisers)) {
            foreach ($arrSanitisers as $sanitiser) {
                try {
                    if (is_string($sanitiser)) {
                        switch ($sanitiser) {
                            case "trim":
                                $varValue = trim((string)$varValue);

                                break;
                            case "clear":
                                $varValue = "";

                                break;
                        }
                    } elseif (is_callable($sanitiser)) {
                        $varValue = $sanitiser($varValue);
                    }
                } catch (\Exception $ex) {
                    //*** Sanitization failed. Continue silently.
                }
            }
        }

        return $varValue;
    }

    /**
     * Pre-sanitize a value using a given set of sanitisers or the preset list already available.
     *
     * @param $varValue
     * @param null|array $arrSanitisers
     * @return string
     */
    public function preSanitize($varValue, $arrSanitisers = null)
    {
        //*** We only support a limited set of sanitisers for pre-sanitization.
        $arrSanitiserWhitelist = ["trim"];

        //*** Use either the provided value or the locally preset value for sanitization.
        $arrSanitisers = (!is_null($arrSanitisers)) ? $arrSanitisers : $this->__sanitisers;

        if (is_array($arrSanitisers)) {
            //*** Make sure we only have white listed sanitisers.
            $arrWhitelisted = [];
            foreach ($arrSanitisers as $varSanitiser) {
                if (is_string($varSanitiser) && in_array($varSanitiser, $arrSanitiserWhitelist)) {
                    $arrWhitelisted[] = $varSanitiser;
                }
            }

            if (count($arrWhitelisted) > 0) {
                $varValue = $this->sanitize($varValue, $arrWhitelisted);
            }
        }

        return $varValue;
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
     * Convert a value to float, considering different values for the decimal and thousand separators.
     *
     * @param float|int|string|null $strValue
     * @return float
     */
    protected static function toFloat(float|int|string|null $strValue): float
    {
        if (strpos((string) $strValue, ".") < strpos((string) $strValue, ",")) {
            $strValue = str_replace(array(".", ","), array("", "."), (string)$strValue);
        } else {
            $strValue = str_replace(",", "", (string) $strValue);
        }

        return (float) $strValue;
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
