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
 * @version 3.0.0
 */

namespace ValidFormBuilder;

/**
 * Create a Multifield element
 *
 * Multifield elements allow you to combine multiple fields horizontally with one label.
 * For example, create a first name + last name field with label "Full name"
 *
 * ```php
 * $objMulti = $objForm->addMultifield("Full name");
 * // Note: when using addField on a multifield, we don't set a label!
 * $objMulti->addField(
 *     "first-name",
 *     ValidForm::VFORM_STRING,
 *     array(),
 *     array(),
 *     // Keep it short, this is just a first name field
 *     array("style" => "width: 50px")
 * );
 * $objMulti->addField("last-name", ValidForm::VFORM_STRING);
 * ```
 *
 * You can also combine select elements to create a date picker:
 *
 * ```php
 * $objMulti = $objForm->addMultiField("Birthdate");
 * $objMulti->addField(
 *     "year",
 *     ValidForm::VFORM_SELECT_LIST,
 *     array(),
 *     array(),
 *     array(
 *         "start" => 1920,
 *         "end" => 2014,
 *         // 'fieldstyle' gets applied on the <select>
 *         // regular 'style' applies on the wrapping <div>
 *         "fieldstyle" => "width: 75px"
 *     )
 * );
 * $objMulti->addField(
 *     "month",
 *     ValidForm::VFORM_SELECT_LIST,
 *     array(),
 *     array(),
 *     array(
 *         "start" => 01,
 *         "end" => 12,
 *         "fieldstyle" => "width: 75px"
 *     )
 * );
 * $objMulti->addField(
 *     "day",
 *     ValidForm::VFORM_SELECT_LIST,
 *     array(),
 *     array(),
 *     array(
 *         "start" => 1,
 *         "end" => 31,
 *         "fieldstyle" => "width: 75px"
 *     )
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 *
 */
class MultiField extends Base
{

    /**
     * Field label
     * @internal
     * @var string
     */
    protected $__label;

    /**
     * Dynamic flag
     * @internal
     * @var boolean
     */
    protected $__dynamic;

    /**
     * Dynamic label
     * @internal
     * @var string
     */
    protected $__dynamicLabel;

    /**
     * Required style
     * @internal
     * @var string
     */
    protected $__requiredstyle;

    /**
     * Fields collection
     * @internal
     * @var \ValidFormBuilder\Collection
     */
    protected $__fields;

    /**
     * Create a new MultiField instance
     *
     * See {@link \ValidFormBuilder\MultiField top of this page} for examples
     *
     * @internal
     * @param string $label The multifield's label
     * @param array $meta The meta array
     */
    public function __construct($label, $meta = array())
    {
        $this->__label = $label;
        $this->__meta = $meta;

        // *** Set label & field specific meta
        $this->__initializeMeta();

        $this->__fields = new Collection();

        $this->__dynamic = $this->getMeta("dynamic", $this->__dynamic);
        $this->__dynamicLabel = $this->getMeta("dynamicLabel", $this->__dynamicLabel);
    }

    /**
     * Add a field to the MultiField collection.
     *
     * Same as {@link \ValidFormBuilder\ValidForm::addField()} with the only difference that the `MultiField::addField()`
     * does not take a field label since that's already set when initialising the `MultiField`.
     *
     * @param string $name Field name
     * @param integer $type Field type
     * @param array $validationRules Validation rules array
     * @param array $errorHandlers Error handling array
     * @param array $meta The meta array
     * @return \ValidFormBuilder\Element
     */
    public function addField($name, $type, $validationRules = array(), $errorHandlers = array(), $meta = array())
    {
        // Creating dynamic fields inside a multifield is not supported.
        if (array_key_exists("dynamic", $meta)) {
            unset($meta["dynamic"]);
        }

        if (array_key_exists("dynamicLabel", $meta)) {
            unset($meta["dynamicLabel"]);
        }

            // Render the field and add it to the multifield field collection.
        $objField = ValidForm::renderField($name, "", $type, $validationRules, $errorHandlers, $meta);

        // *** Set the parent for the new field.
        $objField->setMeta("parent", $this, true);

        $this->__fields->addObject($objField);

        if ($this->__dynamic) {
            // *** The dynamic count can be influenced by a meta value.
            $intDynamicCount = (isset($meta["dynamicCount"])) ? $meta["dynamicCount"] : 0;

            $objHiddenField = new Hidden($objField->getId() . "_dynamic", ValidForm::VFORM_INTEGER, array(
                "default" => $intDynamicCount,
                "dynamicCounter" => true
            ));
            $this->__fields->addObject($objHiddenField);

            $objField->setDynamicCounter($objHiddenField);
        }

        return $objField;
    }

    /**
     * Add text to the multifield.
     *
     * Same as {@link \ValidFormBuilder\ValidForm::addText()}
     *
     * @param string $strText The text to add (can be HTML as well)
     * @param array $meta The meta array
     * @return \ValidFormBuilder\String
     */
    public function addText($strText, $meta = array())
    {
        $objString = new String($strText, $meta);
        $objString->setMeta("parent", $this, true);

        $this->__fields->addObject($objString);

        return $objString;
    }

    /**
     * See {@link \ValidFormBuilder\Base::toHtml()}
     *
     * @internal
     * @param boolean $submitted
     * @param boolean $blnSimpleLayout
     * @param boolean $blnLabel
     * @param boolean $blnDisplayError
     * @return string Generated HTML
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true)
    {
        $strOutput = "";

        if ($this->__dynamic) {
            $intDynamicCount = $this->getDynamicCount();
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayError, $intCount);
            }
        } else {
            $strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayError);
        }

        return $strOutput;
    }

    /**
     * See {@link \ValidFormBuilder\Base::__toHtml()}
     *
     * @internal
     * @param boolean $submitted
     * @param boolean $blnSimpleLayout
     * @param boolean $blnLabel
     * @param boolean $blnDisplayError
     * @param integer $intCount
     * @return string Generated HTML
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true, $intCount = 0)
    {
        // *** Conditional meta should be set before all other meta. Otherwise the set meta is being reset.
        $this->setConditionalMeta();

        // Do nothing if multifield has no child fields.
        if ($this->__fields->count() == 0) {
            return "";
        }

        $blnError = false;
        $arrError = array();

        $strId = "";
        $blnRequired = false;

        foreach ($this->__fields as $field) {
            $objValidator = $field->getValidator();
            if (is_object($objValidator)) {
                // *** Check if this multifield should have required styling.
                if ($objValidator->getRequired()) {
                    $blnRequired = true;
                }

                if ($submitted && ! $objValidator->validate($intCount) && $blnDisplayError) {
                    $blnError = true;

                    $strError = $field->getValidator()->getError($intCount);
                    if (! in_array($strError, $arrError)) {
                        $arrError[] = $strError;
                    }
                }
            }
        }

        // *** We asume that all dynamic fields greater than 0 are never required.
        if ($blnRequired && $intCount == 0) {
            $this->setMeta("class", "vf__required");
        } else {
            $this->setMeta("class", "vf__optional");
        }

        // *** Set custom meta.
        if ($blnError) {
            $this->setMeta("class", "vf__error");
        }

        $this->setMeta("class", "vf__multifield vf__cf");

        $strId = ($intCount == 0) ? $this->getId() : $this->getId() . "_{$intCount}";
        $strOutput = "<div{$this->__getMetaString()} id=\"{$strId}\">\n";

        if ($blnError) {
            $strOutput .= "<p class=\"vf__error\">" . implode("</p><p class=\"vf__error\">", $arrError) . "</p>";
        }

        $strLabel = (! empty($this->__requiredstyle) && $blnRequired) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
        if (! empty($this->__label)) {
            $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
        }

        foreach ($this->__fields as $field) {
            // Skip the hidden dynamic counter fields.
            if (($intCount > 0) && (get_class($field) == "ValidFormBuilder\\Hidden") && $field->isDynamicCounter()) {
                continue;
            }

            $strOutput .= $field->__toHtml($submitted, true, $blnLabel, $blnDisplayError, $intCount);
        }

        if (! empty($this->__tip)) {
            $strOutput .= "<small class=\"vf__tip\"{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        $strOutput .= "</div>\n";

        if ($intCount == $this->getDynamicCount()) {
            $strOutput .= $this->__addDynamicHtml();
        }

        return $strOutput;
    }

    /**
     * Generate dynamic HTML for client-side field duplication
     * @internal
     * @return string
     */
    protected function __addDynamicHtml()
    {
        $strReturn = "";

        if ($this->__dynamic && ! empty($this->__dynamicLabel)) {
            $arrFields = array();
            // Generate an array of field id's
            foreach ($this->__fields as $field) {
                // Skip the hidden dynamic counter fields.
                if ((get_class($field) == "ValidFormBuilder\\Hidden") && $field->isDynamicCounter()) {
                    continue;
                }
                $arrFields[$field->getId()] = $field->getName();
            }

            $strReturn .= "<div class=\"vf__dynamic vf__cf\">";
            $strReturn .= "<a href=\"#\" data-target-id=\"" . implode("|", array_keys($arrFields)) . "\" data-target-name=\"" . implode("|", array_values($arrFields)) . "\">{$this->__dynamicLabel}</a>";
            $strReturn .= "</div>";
        }

        return $strReturn;
    }

    /**
     * Generate Javascript
     * See {@\ValidFormBuilder\Base::toJS()}
     *
     * @internal
     * @see \ValidFormBuilder\Base::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        foreach ($this->__fields as $field) {
            $strOutput .= $field->toJS($this->__dynamic);
        }

        // *** Condition logic.
        if ($this->__dynamic || $intDynamicPosition) {
            $intDynamicCount = $this->getDynamicCount($intDynamicPosition);
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                // *** Render the condition logic per dynamic field.
                $strOutput .= $this->conditionsToJs($intCount);
            }
        } else {
            // *** Condition logic.
            $strOutput .= $this->conditionsToJs();
        }

        return $strOutput;
    }

    /**
     * Validate internal fields
     * @internal
     * @return boolean
     */
    public function isValid()
    {
        $intDynamicCount = $this->getDynamicCount();

        for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
            $blnReturn = $this->__validate($intCount);

            if (! $blnReturn) {
                break;
            }
        }

        return $blnReturn;
    }

    /**
     * Check if multifield is dynamic
     * @internal
     * @return boolean
     */
    public function isDynamic()
    {
        return ($this->__dynamic) ? true : false;
    }

    /**
     * Get the dynamic count of this multifield
     * @internal
     * @return integer
     */
    public function getDynamicCount()
    {
        $intReturn = 0;

        if ($this->__dynamic) {
            $objCounters = $this->getCountersRecursive($this->getFields());

            foreach ($objCounters as $objCounter) {
                $intCounterValue = $objCounter->getValidator()->getValue();
                if ($intCounterValue > $intReturn) {
                    $intReturn = $intCounterValue;
                }
            }

            if ($intReturn > 0) {
                // Equalize all counter values inside this area
                foreach ($objCounters as $objCounter) {
                    $objCounter->setDefault($intReturn);
                }
            }
        }

        return $intReturn;
    }

    /**
     * Get Fields collection
     * @internal
     * @return \ValidFormBuilder\Collection
     */
    public function getFields()
    {
        return $this->__fields;
    }

    /**
     * Get value - placeholder
     * @internal
     * @return boolean
     */
    public function getValue()
    {
        return true;
    }

    /**
     * Get MultiField ID
     * @internal
     * @return string
     */
    public function getId()
    {
        return $this->getName();
    }

    /**
     * Get field type - placeholder to overwrite default logic
     * @internal
     * @return integer
     */
    public function getType()
    {
        return 0;
    }

    /**
     * Loop through all child fields and check their values. If one value is not empty, the MultiField has content.
     *
     * @internal
     * @param integer $intCount The current dynamic count.
     * @return boolean True if multifield has content, false if not.
     */
    public function hasContent($intCount = 0)
    {
        $blnReturn = false;

        foreach ($this->__fields as $objField) {
            if (get_class($objField) !== "ValidFormBuilder\\Hidden") {
                $objValidator = $objField->getValidator();
                if (is_object($objValidator)) {
                    $varValue = $objValidator->getValue($intCount);

                    if (! empty($varValue)) {
                        $blnReturn = true;

                        break;
                    }
                }
            }
        }

        return $blnReturn;
    }

    /**
     * Check if MultiField has internal fields in it's collection
     * @internal
     * @return boolean
     */
    public function hasFields()
    {
        return ($this->__fields->count() > 0) ? true : false;
    }

    /**
     * Store data in the current object.
     *
     * See {@link \ValidFormBuilder\Base::setData()} for a full description
     *
     * @param string $strKey The key for this storage
     * @param mixed $varValue The value to store
     * @return boolean True if set successful, false if not.
     */
    public function setData($strKey = null, $varValue = null)
    {
        $varReturn = false;
        $this->__meta["data"] = (isset($this->__meta["data"])) ? $this->__meta["data"] : array();

        if (isset($this->__meta["data"])) {
            if (! is_null($strKey) && ! is_null($varValue)) {
                $this->__meta["data"][$strKey] = $varValue;
            }
        }

        return isset($this->__meta["data"][$strKey]);
    }

    /**
     * Get a value from the internal data array.
     *
     * See {@link \ValidFormBuilder\Base::getData()} for a full description
     *
     * @param string $key The key of the data attribute to return
     * @return mixed If a key is provided, return it's value. If no key provided, return the whole data array.
     * If anything is not set or incorrect, return false.
     */
    public function getData($key = null)
    {
        $varReturn = false;

        if (isset($this->__meta["data"])) {
            if ($key == null) {
                $varReturn = $this->__meta["data"];
            } else {
                if (isset($this->__meta["data"][$key])) {
                    $varReturn = $this->__meta["data"][$key];
                }
            }
        }

        return $varReturn;
    }

    /**
     * Validate the fields in the collection
     * @internal
     * @param integer $intCount Dynamic counter
     * @return boolean True if all fields are valid, false if not.
     */
    private function __validate($intCount = null)
    {
        $blnReturn = true;

        foreach ($this->__fields as $field) {
            if (! $field->isValid($intCount)) {
                $blnReturn = false;
                break;
            }
        }

        return $blnReturn;
    }
}
