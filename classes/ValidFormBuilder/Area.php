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
 * Group fields together in an Area
 *
 * An area is about the same as a fieldset but an Area has more interactive options like the 'active'
 * property or even the 'dynamic' meta.
 *
 * An Area can be used to group form fields together. When an Area is active, it can toggle the disabled state
 * on all it's child form fields using the auto-generated checkbox in the Area's legend.
 *
 * #### Example; Active area
 * ```php
 * $objArea = $objForm->addArea("Disable fields", true, "fields-disabled");
 * $objArea->addField(
 *     "first-name",
 *     "First name",
 *     ValidForm::VFORM_STRING,
 *     array(
 *         // Make this field required
 *         "required" => true
 *     ),
 *     array(
 *         // Show this error to indicate this is an required field if no value is submitted
 *         "required" => "This field is required"
 *     )
 * );
 * $objArea->addField(
 *     "last-name",
 *     "Last name",
 *     ValidForm::VFORM_STRING,
 *     array(
 *         // Make this field required
 *         "required" => true
 *     ),
 *     array(
 *         // Show this error to indicate this is an required field if no value is submitted
 *         "required" => "This field is required"
 *     )
 * );
 * ```
 *
 * #### Example 2; Adding a string field to the area
 * ```php
 * $objArea = $objForm->addArea("Cool area");
 * $objArea->addField("first-name", "First name", ValidForm::VFORM_STRING);
 * ```
 *
 * #### Example 3; Addding a paragraph to the Area
 * ```php
 * $objArea->addParagraph(
 *     "Cool paragraph with lots of text in it. It's an absolute must-read.",
 *     "You must read this"
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 *
 * @method string getLabel() getLabel() Returns the value of `$__label`
 * @method void setLabel() setLabel(string $value) Overwrites the value of `$__label`
 * @method string getRequiredStyle() getRequiredStyle() Returns the value of `$__requiredstyle`
 * @method void setRequiredStyle() setRequiredStyle(string $value) Overwrites the value of `$__requiredstyle`
 */
class Area extends Base
{
    /**
     * The Area's label, presented as a 'legend' header above the area
     * @var string
     */
    protected $__label;

    /**
     * Make this an active area with __active set on true. When active, the entire area
     * can be enabled and disabled with a checkbox.
     * @var boolean
     */
    protected $__active;

    /**
     * Use in combination with 'active'; defines if the active area is checked by default or not
     * @var boolean
     */
    protected $__checked;

    /**
     * Using the dynamic 'setRequiredStyle()', you can add for instance an asterix to each required field like so:
     * $this->setRequiredStyle('%s *'); // First show the label, %s, then show an asterix after the label.
     * @var string
     */
    protected $__requiredstyle;

    /**
     * The child fields collection
     * @var \ValidFormBuilder\Collection
     */
    protected $__fields;

    /**
     * Create a new Area instance
     *
     * The label is used as a small 'header' above the area. When setting an area to 'active', this label becomes
     * clickable using a checkbox. This clickable header can toggle child fields to be enabled / disabled.
     *
     * @param string $label The Area's label
     * @param boolean $active Whether the area should be active or not.
     * When active, a checkbox will be prefixed to the header.
     * @param string $name The name for this area
     * @param boolean $checked Whether or not the active area should be checked by default
     * @param array $meta The optional meta array
     */
    public function __construct($label, $active = false, $name = null, $checked = false, $meta = array())
    {
        $this->__label = $label;
        $this->__active = $active;
        $this->__name = $name;
        $this->__checked = $checked;
        $this->__meta = $meta;

        // *** Set label & field specific meta
        $this->__initializeMeta();

        $this->__fields = new Collection();

        $this->__dynamic = $this->getMeta("dynamic", null);
        $this->__dynamicLabel = $this->getMeta("dynamicLabel", null);
        $this->__dynamicRemoveLabel = $this->getMeta("dynamicRemoveLabel", null);
    }

    /**
     * Add a field to the Area.
     *
     * See {@link \ValidFormBuilder\Area top of the page} for an example
     *
     * @param string $name
     * @param string $label
     * @param integer $type One of the ValidForm::VFORM_ field types
     * @param array $validationRules Standard validation rules array
     * @param array $errorHandlers Standard error handler array
     * @param array $meta Standard meta array
     * @return null|\ValidFormBuilder\Element Returns an instance of the field type generated
     */
    public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array())
    {
        $objField = ValidForm::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);

        $objField->setMeta("parent", $this, true);

        $this->__fields->addObject($objField);

        if ($this->__dynamic || $objField->isDynamic()) {
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
     * Add paragraph to Area
     *
     * #### Example
     * ```php
     * $objArea->addParagraph(
     *     "Cool paragraph with lots of text in it. It's an absolute must-read.",
     *     "You must read this"
     * );
     * ```
     *
     * @param string $strBody The paragraph's body text
     * @param string $strHeader The paragraph's optional header
     * @param array $meta Standard meta array
     * @return \ValidFormBuilder\Paragraph
     */
    public function addParagraph($strBody, $strHeader = "", $meta = array())
    {
        $objParagraph = new Paragraph($strHeader, $strBody, $meta);

        $objParagraph->setMeta("parent", $this, true);

        // *** Add field to the fieldset.
        $this->__fields->addObject($objParagraph);

        return $objParagraph;
    }

    /**
     * Add a multifield to the Area
     *
     * @param string $label The multifield's label
     * @param array $meta The standard meta array
     * @return \ValidFormBuilder\MultiField
     */
    public function addMultiField($label = null, $meta = array())
    {
        if (!array_key_exists("dynamic", $meta)) {
            $meta["dynamic"] = $this->__dynamic;
        }

        // *** Overwrite dynamic settings. We cannot have a dynamic multifield inside a dynamic area.
        if ($this->__dynamic) {
            $meta["dynamic"] = $this->__dynamic;
            $meta["dynamicLabel"] = "";
        }

        $objField = new MultiField($label, $meta);

        $objField->setRequiredStyle($this->__requiredstyle);
        $objField->setMeta("parent", $this, true);

        $this->__fields->addObject($objField);

        return $objField;
    }

    /**
     * Render the Area and it's children with toHtml()
     *
     * @param boolean $submitted Define if the area has been submitted and propagate that flag to the child fields
     * @param boolean $blnSimpleLayout Only render in simple layout mode
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors Display generated errors
     * @return string Rendered Area
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        $strOutput = "";
        $intDynamicCount = $this->getDynamicCount();
        for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++) {
            $strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
        }

        return $strOutput;
    }

    /**
     * Verify if any of the child fields in this area has submitted data
     *
     * @param integer $intCount Optional counter to do the same for dynamic multifields.
     * @return boolean True if area childs contain submitted data, false if not.
     */
    public function hasContent($intCount = 0)
    {
        $blnReturn = false;

        foreach ($this->__fields as $objField) {
            if (get_class($objField) !== "ValidFormBuilder\\Hidden" || get_class($objField) !== "ValidFormBuilder\\Paragraph") {
                if (get_class($objField) == "ValidFormBuilder\\MultiField") {
                    $blnReturn = $objField->hasContent($intCount);
                    if ($blnReturn) {
                        break;
                    }
                } else {
                    if ($objField instanceof Element) {
                        $varValue = $objField->getValidator()->getValue($intCount);

                        if (! empty($varValue)) {
                            $blnReturn = true;
                            break;
                        }
                    }
                }
            }
        }

        return $blnReturn;
    }

    /**
     * Same as {@link \ValidFormBuilder\Area::toHtml()} but with dynamic counter as extra parameter
     *
     * @param boolean $submitted Define if the area has been submitted and propagate that flag to the child fields
     * @param boolean $blnSimpleLayout Only render in simple layout mode
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors Display generated errors
     * @param integer $intCount The dynamic count of this area
     * @return string Rendered Area
     */
    protected function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        // *** Conditional meta should be set before all other meta. Otherwise the set meta is being reset.
        $this->setConditionalMeta();

        $strName = ($intCount == 0) ? $this->getName() : $this->getName() . "_" . $intCount;

        if ($this->__active && $this->__checked && ! $submitted) {
            $this->setFieldMeta("checked", "checked", true);
        }

        if ($this->__active && $submitted && $this->hasContent($intCount)) {
            $this->setFieldMeta("checked", "checked", true);
        }

        $this->setMeta("class", "vf__area");
        if ($this->__active && is_null($this->getFieldMeta("checked", null))) {
            $this->setMeta("class", "vf__disabled");
        }

        if ($intCount > 0) {
            $this->setMeta("class", "vf__clone");
        }

        //*** If this is a removable field, add corresponding class
        if ($this->isRemovable()) {
            $this->setMeta("class", "vf__removable");
        }

        //*** Add data-dynamic="original" or data-dynamic="clone" attributes to dynamic fields
        if ($this->isDynamic()) {
            if ($intCount === 0) {
                // This is the first, original element. Make sure to define that.
                $this->setMeta('data-dynamic', 'original', true);
            } else {
                $this->setMeta('data-dynamic', 'clone', true);
                $this->setMeta("class", "vf__clone");
            }
        }

        $strId = ($intCount == 0) ? " id=\"{$this->getId()}\"" : "";
        $strOutput = "<fieldset{$this->__getMetaString()}{$strId}>\n";

        if ($this->__active) {
            $strCounter = ($intCount == 0 && $this->__dynamic) ? " <input type='hidden' name='{$strName}_dynamic' value='{$intCount}' id='{$strName}_dynamic'/>" : "";
            $label = "<label for=\"{$strName}\"><input type=\"checkbox\" name=\"{$strName}\" id=\"{$strName}\"{$this->__getFieldMetaString()} /> {$this->__label}{$strCounter}</label>";
        } else {
            $label = $this->__label;
        }

        if (! empty($this->__label)) {
            $strOutput .= "<legend>{$label}</legend>\n";
        }

        foreach ($this->__fields as $objField) {
            if (($intCount > 0) && get_class($objField) == "ValidFormBuilder\\Hidden" && $objField->isDynamicCounter()) {
                continue;
            }

            // $submitted = ($this->__active && !$blnHasContent) ? FALSE : $submitted;
            $strOutput .= $objField->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
        }

        if ($this->isRemovable()) {
            $this->setMeta("dynamicRemoveLabelClass", "vf__removeLabel");

            $strOutput .= $this->getRemoveLabelHtml();
        }

        $strOutput .= "</fieldset>\n";

        if ($intCount == $this->getDynamicCount()) {
            $strOutput .= $this->getDynamicHtml($intCount);
        }

        return $strOutput;
    }

    /**
     * Generate extra HTML output to facilitate the dynamic duplication logic
     * @param int $intCount
     * @return string
     */
    protected function getDynamicHtml($intCount = 0)
    {
        $strReturn = "";

        if ($this->__dynamic && ! empty($this->__dynamicLabel)) {
            $arrFields = array();
            // Generate an array of field id's
            foreach ($this->__fields as $field) {
                switch (get_class($field)) {
                    case "ValidFormBuilder\\MultiField":
                        /* @var $subField Element */
                        foreach ($field->getFields() as $subField) {
                            // Skip the hidden dynamic counter fields.
                            if ((get_class($subField) == "ValidFormBuilder\\Hidden") && $subField->isDynamicCounter()) {
                                continue;
                            }

                            if (!empty($field->getName())) {
                                $arrFields[$subField->getId()] = $subField->getName();
                            }
                        }

                        break;
                    default:
                        // Skip the hidden dynamic counter fields.
                        if ((get_class($field) == "ValidFormBuilder\\Hidden") && $field->isDynamicCounter()) {
                            continue 2;
                        }

                        if (!empty($field->getName())) {
                            $arrFields[$field->getId()] = $field->getName();
                        }

                        break;
                }
            }

            //*** Store the active area's checkbox in the dynamic fields array as well
            $areaName = ($intCount == 0) ? $this->getName() : $this->getName() . "_" . $intCount;
            if ($this->__active) {
                $arrFields[$areaName] = $areaName;
            }

            $strReturn .= "<div class=\"vf__dynamic\"{$this->getDynamicButtonMeta()}>";
            $strReturn .= "<a href=\"#\" data-target-id=\"" . implode("|", array_keys(array_filter($arrFields)))
                . "\" data-target-name=\"" . implode("|", array_values(array_filter($arrFields)))
                . "\"{$this->__getDynamicLabelMetaString()}>{$this->__dynamicLabel}</a>";
            $strReturn .= "</div>";
        }

        return $strReturn;
    }

    /**
     * Generate Javascript code.
     *
     * See {@link \ValidFormBuilder\Base::toJs() Base::toJs()}
     *
     * @param integer $intDynamicPosition The dynamic position counter
     * @return string Generated javascript code
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strReturn = "";

        foreach ($this->__fields as $field) {
            $strReturn .= $field->toJS($this->__dynamic);
        }

        $strReturn .= $this->conditionsToJs($intDynamicPosition);

        return $strReturn;
    }

    /**
     * Check if this is an active area
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->__active;
    }

    /**
     * Verify if all submitted data of this area and it's children is valid.
     *
     * @return boolean
     */
    public function isValid()
    {
        $blnReturn = true;

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
     * Get the dynamic counter value if this is an dynamic area.
     * @return integer Defaults to 0 if not an dynamic area. If dynamic, this returns the number of times the user
     * duplicated this area.
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
     * Return all children in a Collection
     * @return \ValidFormBuilder\Collection
     */
    public function getFields()
    {
        return $this->__fields;
    }

    /**
     * If this is an active area, this will return the value of the checkbox.
     * @param string $intCount Dynamic counter, defaults to null
     * @return boolean
     */
    public function getValue($intCount = null)
    {
        $strName = ($intCount > 0) ? $this->__name . "_" . $intCount : $this->__name;
        $value = ValidForm::get($strName);
        return (($this->__active && ! empty($value)) || ! $this->__active) ? true : false;
    }

    /**
     * Return the Area name
     * @return string
     */
    public function getId()
    {
        return $this->getName() . "_wrapper";
    }

    /**
     * For API compatibility, we've added the placeholder method 'getType'
     * @return number
     */
    public function getType()
    {
        return 0;
    }

    /**
     * Check if this area contains child objects.
     * @return boolean True if fields collection > 0, false if not.
     */
    public function hasFields()
    {
        return ($this->__fields->count() > 0) ? true : false;
    }

    /**
     * Validate this Area and it's children's submitted values
     *
     * @param string $intCount The dynamic counter
     * @return boolean True if Area and children are valid, false if not.
     */
    private function __validate($intCount = null)
    {
        $blnReturn = true;

        foreach ($this->__fields as $field) {
            // Note: hasContent is only accurate if isValid() is called first ...
            if (! $field->isValid($intCount)) {
                $blnReturn = false;
                break;
            }
        }

        // ... therefore, check if the area is empty after validation of all the fields.
        if ($this->__active && ! $this->hasContent($intCount)) {
            $blnReturn = true;
        }

        return $blnReturn;
    }
}
