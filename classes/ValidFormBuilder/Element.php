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
 * Element Class
 *
 * The base class for most form elements
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 *
 * @internal
 */
class Element extends Base
{

    /**
     * Element name
     * @internal
     * @var string
     */
    protected $__name;
    /**
     * Element label
     * @internal
     * @var string
     */
    protected $__label;
    /**
     * Element tip text
     * @internal
     * @var string
     */
    protected $__tip = null;
    /**
     * Element type
     * @internal
     * @var integer
     */
    protected $__type;
    /**
     * Element hint value
     * @internal
     * @var string
     */
    protected $__hint = null;
    /**
     * Element default value
     * @internal
     * @var string
     */
    protected $__default = null;
    /**
     * Element dynamic flag
     * @internal
     * @var boolean
     */
    protected $__dynamic = null;
    /**
     * Element dynamic counter
     * @internal
     * @var integer
     */
    protected $__dynamiccounter = false;
    /**
     * Element dynamic label
     * @internal
     * @var string
     */
    protected $__dynamicLabel = null;
    /**
     * Element required style
     * @internal
     * @var string
     */
    protected $__requiredstyle;
    /**
     * Element Validator object
     * @internal
     * @var Validator
     */
    protected $__validator;
    /**
     * Sanitize actions
     * @internal
     * @var array
     */
    protected $__sanitize;

    /**
     * Create new element
     *
     * @internal
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
        $this->__dynamiccounter = (! is_null($this->getMeta("dynamicCounter", null))) ? true : $this->__dynamiccounter;

        $this->__sanitize = $this->getMeta("sanitize", $this->__sanitize);

        // $this->__validator = new FieldValidator($name, $type, $validationRules, $errorHandlers, $this->__hint);
        $this->__validator = new FieldValidator($this, $validationRules, $errorHandlers);
    }

    /**
     * Checks if this element is a dynamic counter for another element
     * @internal
     * @return boolean `True` if it is, `false` if not. Default `false`.
     */
    public function isDynamicCounter()
    {
        return false;
    }

    /**
     * Set type class
     * @internal
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
     * @internal
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
     * @internal
     * @param boolean $submitted
     * @param boolean $blnSimpleLayout
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors
     * @param integer $intCount
     * @return string
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        return $this->toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
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
     * @param number $intDynamicPosition Set the error message on a specific dynamic field with this index
     */
    public function setError($strError, $intDynamicPosition = 0)
    {
        // *** Override the validator message.
        $this->__validator->setError($strError, $intDynamicPosition);
    }

    /**
     * @see \ValidFormBuilder\Base::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        return "alert('Field type of field {$this->__name} not defined.');\n";
    }

    /**
     * Generate a random ID
     * @internal
     * @param string $name Fieldname
     * @return string
     */
    public function getRandomId($name)
    {
        $strReturn = $name;

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
     * @return boolean True if field validates, false if not.
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
     * Check if the current field is a dynamic field.
     *
     * @internal
     * @return boolean True if dynamic, false if not.
     */
    public function isDynamic()
    {
        return $this->__dynamic;
    }

    /**
     * Get the number of dynamic fields from the dynamic counter field.
     * @internal
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
     * Add a dynamic counter object
     * @internal
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
        $varValue = $this->sanitize($varValue);

        return $varValue;
    }

    /**
     * Sanitize a value according to the order of actions in __sanitize.
     *
     * @param mixed $varValue
     */
    protected function sanitize($varValue)
    {
        if (is_array($this->__sanitize)) {
            foreach ($this->__sanitize as $value) {
                try {
                    if (is_string($value)) {
                        switch ($value) {
                            case "trim":
                                $varValue = trim($varValue);
                                break;
                        }
                    } else if (is_callable($value)) {
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
     * @internal
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
     * @internal
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
                    if (isset($this->__default[$intDynamicPosition]) && strlen($this->__default[$intDynamicPosition]) > 0) {
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

        return $varReturn;
    }
}
