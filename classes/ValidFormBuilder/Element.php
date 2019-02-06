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
 * Element Class
 *
 * The base class for most form elements
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@cattlea.com>
 * @version Release: 3.0.0
 *
 * @method string getLabel() getLabel() Returns the value of `$__label`
 * @method void setLabel() setLabel(string $value) Overwrites the value of `$__label`
 * @method string getTip() getTip() Returns the value of `$__tip`
 * @method void setTip() setTip(string $value) Overwrites the value of `$__tip`
 * @method integer getType() getType() Returns the value of `$__type`
 * @method void setType() setType(integer $value) Overwrites the value of `$__type`
 * @method string getHint() getHint() Returns the value of `$__hint`
 * @method void setHint() setHint(string $value) Overwrites the value of `$__hint`
 * @method string getRequiredStyle() getRequiredStyle() Returns the value of `$__requiredstyle`
 * @method void setRequiredStyle() setRequiredStyle(string $value) Overwrites the value of `$__requiredstyle`
 * @method FieldValidator getValidator() getValidator() Returns the value of `$__validator`
 * @method void setValidator() setValidator(FieldValidator $value) Overwrites the value of `$__validator`
 * @method \ValidFormBuilder\Element addField() addField($name, $type, $validationRules = array(), $errorHandlers = array(), $meta = array()) Adds another field to this field if this field is a `container`
 */
class Element extends Base
{
    /**
     * Element label
     * @var string
     */
    protected $__label;

    /**
     * Element tip text
     * @var string
     */
    protected $__tip = null;

    /**
     * Element type
     * @var integer
     */
    protected $__type;

    /**
     * Element hint value
     * @var string
     */
    protected $__hint = null;

    /**
     * Element default value
     * @var string
     */
    protected $__default = null;

    /**
     * Element required style
     * @var string
     */
    protected $__requiredstyle;

    /**
     * Element Validator object
     * @var FieldValidator
     */
    protected $__validator;

    /**
     * Sanitize actions
     * @var array
     */
    protected $__sanitize;

    /**
     * Sanitize actions for display values
     * @var array
     */
    protected $__displaySanitize;

    /**
     * Create new element
     *
     * @param string $name Field name
     * @param integer $type Field type
     * @param string $label Field label
     * @param array $validationRules Validation rules
     * @param array $errorHandlers Error rules
     * @param array $meta The meta array
     */
    public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array())
    {
        // Set meta class
        $this->setClass($type, $meta);

        $this->__id = (strpos($name, "[]") !== false) ? $this->getRandomId($name) : $name;
        $this->__name = $name;
        $this->__label = $label;
        $this->__type = $type;
        $this->__meta = $meta;

        // *** Set label & field specific meta
        $this->__initializeMeta();

        $this->__parent = $this->getMeta("parent", null);
        $this->__tip = $this->getMeta("tip", $this->__tip);
        $this->__hint = $this->getMeta("hint", $this->__hint);
        $this->__default = $this->getMeta("default", $this->__default);
        $this->__dynamic = $this->getMeta("dynamic", $this->__dynamic);
        $this->__dynamicLabel = $this->getMeta("dynamicLabel", $this->__dynamicLabel);
        $this->__dynamicRemoveLabel = $this->getMeta("dynamicRemoveLabel", $this->__dynamicRemoveLabel);
        $this->__dynamiccounter = (! is_null($this->getMeta("dynamicCounter", null))) ? true : $this->__dynamiccounter;

        $this->__sanitize = $this->getMeta("sanitize", $this->__sanitize);
        $this->__displaySanitize = $this->getMeta("displaySanitize", $this->__displaySanitize);

        // $this->__validator = new FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
        $this->__validator = new FieldValidator($this, $validationRules, $errorHandlers);
    }

    /**
     * Checks if this element is a dynamic counter for another element
     * @return boolean `True` if it is, `false` if not. Default `false`.
     */
    public function isDynamicCounter()
    {
        return false;
    }

    /**
     * Set type class
     * @param integer $type
     * @param array $meta
     */
    protected function setClass($type, &$meta)
    {
        switch ($type) {
            case ValidForm::VFORM_STRING:
                $this->setFieldMeta("class", "vf__string");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_WORD:
                $this->setFieldMeta("class", "vf__word");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_EMAIL:
                $this->setFieldMeta("class", "vf__email");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_URL:
            case ValidForm::VFORM_SIMPLEURL:
                $this->setFieldMeta("class", "vf__url");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_CUSTOM:
                $this->setFieldMeta("class", "vf__custom");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_CURRENCY:
                $this->setFieldMeta("class", "vf__currency");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_DATE:
                $this->setFieldMeta("class", "vf__date");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_NUMERIC:
                $this->setFieldMeta("class", "vf__numeric");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_INTEGER:
                $this->setFieldMeta("class", "vf__integer");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_PASSWORD:
                $this->setFieldMeta("class", "vf__password");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_HTML:
                $this->setFieldMeta("class", "vf__html");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_CUSTOM_TEXT:
                $this->setFieldMeta("class", "vf__custom");
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_TEXT:
                $this->setFieldMeta("class", "vf__text");
                break;
            case ValidForm::VFORM_FILE:
                $this->setFieldMeta("class", "vf__file");
                break;
            case ValidForm::VFORM_BOOLEAN:
                $this->setFieldMeta("class", "vf__checkbox");
                break;
            case ValidForm::VFORM_RADIO_LIST:
            case ValidForm::VFORM_CHECK_LIST:
                $this->setFieldMeta("class", "vf__list");
                break;
            case ValidForm::VFORM_SELECT_LIST:
                if (! isset($meta["multiple"])) {
                    $this->setFieldMeta("class", "vf__one");
                } else {
                    $this->setFieldMeta("class", "vf__multiple");
                }

                $this->setFieldMeta("class", "vf__select");
                break;
        }
    }

    /**
     * Generate HTML output
     *
     * @param boolean $submitted Force if this field should behave like a submitted field or not (e.g. validate etc.)
     * @param boolean $blnSimpleLayout Force 'simple layout' output -- no labels and wrapping divs.
     * @param boolean $blnLabel Show label. Don't show if false.
     * @param boolean $blnDisplayErrors Show errors (default true). Don't show errors if false.
     * @return string
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        return "Field type not defined.";
    }

    /**
     * Generate HTML output for specific dynamic count
     *
     * @param boolean $submitted
     * @param boolean $blnSimpleLayout
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors
     * @param integer $intCount
     * @return string
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        return $this->toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
    }

    /**
     * Set a (custom) error message on this specific element
     *
     * This is mostly used when doing custom server-side validation like validating a username existance. Example:
     * ```php
     * if ($objForm->isSubmitted() && $objForm->isValid()) {
     *     $objUserNameField = $objForm->getValidField("username");
     *     $strUserName = $objUserNameField->getValue();
     *     if (User::exists($strUserName)) {
     *         $objUserNameField->setError("User already exists.");
     *         $strOutput = $objForm->toHtml();
     *     } else {
     *         $strOutput = "Account created successfully with the following details:<br />";
     *         $strOutput .= $objForm->valuesAsHtml();
     *     }
     * }
     * ```
     *
     * @param string $strError The error message
     * @param integer $intDynamicPosition Set the error message on a specific dynamic field with this index
     */
    public function setError($strError, $intDynamicPosition = 0)
    {
        // *** Override the validator message.
        $this->__validator->setError($strError, $intDynamicPosition);
    }

    /**
     * Placeholder method
     * @param integer $intDynamicPosition Dynamic position counter
     * @see \ValidFormBuilder\Base::toJS()
     * @return string
     */
    public function toJS($intDynamicPosition = 0)
    {
        return "alert('Field type of field {$this->__name} not defined.');\n";
    }

    /**
     * Generate a random ID for a given field name to prevent having two fields with the same name
     * @param string $name Field name
     * @return string
     */
    public function getRandomId($name)
    {
        if (strpos($name, "[]") !== false) {
            $strReturn = str_replace("[]", "_" . rand(100000, 900000), $name);
        } else {
            $strReturn = $name . "_" . rand(100000, 900000);
        }

        return $strReturn;
    }

    /**
     * Validate the current field.
     * This is a wrapper method to call the Validator->validate() method.
     * Although you could validate fields on a per-field basis with this method, this is mostly used internally.
     * For instance, when {@link \ValidFormBuilder\ValidForm::validate()} is called, it loops trough it's elements
     * collection and calls this method for each element it finds.
     *
     * @see \ValidFormBuilder\Validator::validate()
     * @param null $intCount Optional. If set, only the dynamic field with this index will be validated.
     * @return bool True if field validates, false if not.
     */
    public function isValid($intCount = null)
    {
        $blnReturn = false;
        $intDynamicCount = $this->getDynamicCount();

        if (is_null($intCount)) {
            // No specific dynamic count is set, loop through dynamic fields internally
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $blnReturn = $this->__validator->validate($intCount);

                if (! $blnReturn) {
                    break;
                }
            }
        } else {
            // Validate just one, we're looping through the external fields externally
            $blnReturn = $this->__validator->validate($intCount);
        }

        return $blnReturn;
    }

    /**
     * Get the number of dynamic fields from the dynamic counter field.
     *
     * @param bool $blnParentIsDynamic
     * @return integer The dynamic count of this field
     */
    public function getDynamicCount($blnParentIsDynamic = false)
    {
        $intReturn = 0;

        if (($this->__dynamic || $blnParentIsDynamic) && is_object($this->__dynamiccounter)) {
            $intReturn = $this->__dynamiccounter->getValidator()->getValue();
        }

        return (int) $intReturn;
    }

    /**
     * Render html element needed for dynamic duplication client-side
     * @return string
     */
    protected function getDynamicHtml()
    {
        $strReturn = "";

        if ($this->__dynamic && ! empty($this->__dynamicLabel)) {
            $strReturn = "<div class=\"vf__dynamic\"><a href=\"#\" data-target-id=\"{$this->__id}\" "
                . "data-target-name=\"{$this->__name}\"{$this->__getDynamicLabelMetaString()}>"
                . $this->__dynamicLabel . "</a></div>\n";
        }

        return $strReturn;
    }

    /**
     * Add a dynamic counter object
     * @param \ValidFormBuilder\Element $objCounter
     */
    public function setDynamicCounter(&$objCounter)
    {
        $this->__dynamiccounter = $objCounter;
    }

    /**
     * Get the *valid* value of the current field.
     *
     * @param integer $intDynamicPosition Optional parameter to get the value of a dynamic field.
     * @return mixed The valid value of this field. If validation fails, it returns null.
     */
    public function getValue($intDynamicPosition = 0)
    {
        $varValue = null;

        if ($intDynamicPosition > 0) {
            $objValidator = $this->__validator;
            $objValidator->validate($intDynamicPosition);

            $varValue = $objValidator->getValidValue($intDynamicPosition);
        } else {
            $varValue = $this->__validator->getValidValue();
        }

        //*** Sanitize the value before returning.
        $varValue = $this->sanitize($varValue, $this->__sanitize);

        return $varValue;
    }

    /**
     * Sanitize a value according to the order of actions in a sanitize array.
     *
     * @param mixed $varValue
     * @param null|array $sanitizations
     * @return mixed|string
     */
    protected function sanitize($varValue, $sanitizations)
    {
        if (is_array($sanitizations)) {
            foreach ($sanitizations as $value) {
                try {
                    if (is_string($value)) {
                        switch ($value) {
                            case "trim":
                                $varValue = trim($varValue);

                                break;
                            case "clear":
                                $varValue = "";

                                break;
                        }
                    } elseif (is_callable($value)) {
                        $varValue = $value($varValue);
                    }
                } catch (\Exception $ex) {
                    //*** Sanitisation failed. Continue silently.
                }
            }
        }

        return $varValue;
    }

    /**
     * Placeholder function to determine wheter or not a field contains other fields.
     * @return boolean Return false by default.
     */
    public function hasFields()
    {
        return false;
    }

    /**
     * Set a new name for this element
     *
     * This method also updates the name in the elements Validator instance.
     *
     * @param string $strName The new name
     */
    public function setName($strName)
    {
        parent::setName($strName);

        if (is_object($this->__validator)) {
            $this->__validator->setFieldName($strName);
        }
    }

    /**
     * Get default value
     *
     * @return array|string
     */
    public function getDefault()
    {
        return $this->__default;
    }

    /**
     * Set default value on this element
     * @param array|string $varValue The value to set as default value
     */
    public function setDefault($varValue)
    {
        $this->__default = $varValue;
    }

    /**
     * Get the value of the field.
     * If the value is *valid* then it will return that value, otherwise the invalid value is returned.
     *
     * @param boolean $submitted Indicate if the form is submitted.
     * @param integer $intDynamicPosition The position of the field in a dynamic field setup.
     * @return string|null
     */
    public function __getValue($submitted = false, $intDynamicPosition = 0)
    {
        $varReturn = null;

        if ($submitted) {
            if ($this->__validator->validate($intDynamicPosition)) {
                $varReturn = $this->__validator->getValidValue($intDynamicPosition);
            } else {
                $varReturn = $this->__validator->getValue($intDynamicPosition);
            }
        } else {
            if (is_array($this->__default)) {
                if ($this->isDynamic()) {
                    if (isset($this->__default[$intDynamicPosition])
                            && strlen($this->__default[$intDynamicPosition]) > 0) {
                        $varReturn = $this->__default[$intDynamicPosition];
                    }
                } else {
                    $varReturn = $this->__default;
                }
            } else {
                if (strlen($this->__default) > 0) {
                    $varReturn = $this->__default;
                } elseif (strlen($this->__hint) > 0) {
                    $varReturn = $this->__hint;
                }
            }
        }

        if (! $varReturn && ((get_class($this) == "ValidFormBuilder\\Hidden") && $this->isDynamicCounter())) {
            $varReturn = (int) 0;
        }

        //*** Sanitize the value before returning.
        $varReturn = $this->sanitize($varReturn, $this->__displaySanitize);

        return $varReturn;
    }
}
