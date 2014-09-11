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
 * Create a group of radio buttons or checkboxes
 *
 * Use the Group object to create a group of either Radio buttons or Checkboxes.
 *
 * #### Example 1; Standard way of creating a checkbox/radio button list
 * ```php
 * $objForm->addField(
 *     "rating",
 *     "Rate ValidForm Builder",
 *     // Replace with ValidForm::VFORM_RADIO_LIST for radio buttons
 *     ValidForm::VFORM_CHECK_LIST
 * );
 * $objSelect->addField("Awesome", 1);
 * $objSelect->addField("Great", 2);
 * $objSelect->addField("Super Cool", 3, true); // This item is selected by default
 * $objSelect->addField("Splendid", 4);
 * $objSelect->addField("Best thing ever happened", 5);
 *
 * //*** Read submitted value
 * if ($objForm->isSubmitted() && $objForm->isValid()) {
 *     // When 'Awesome' and 'Great' is selected, $arrCheckboxValue equals: array('1', '2')
 *     $arrCheckboxValue = $objForm->getValidField("rating")->getValue();
 * }
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class Group extends Element
{
    /**
     * Internal fields collection
     * @internal
     * @var \ValidFormBuilder\Collection
     */
    protected $__fields;

    /**
     * Construct new Group element
     *
     * See {@link \ValidFormBuilder\Base::addField()}
     *
     * @internal
     * @param string $name
     * @param integer $type
     * @param string $label
     * @param array $validationRules
     * @param array $errorHandlers
     * @param array $meta
     */
    public function __construct($name, $type, $label = "", $validationRules = array(), $errorHandlers = array(), $meta = array())
    {
        $this->__fields = new Collection();

        parent::__construct($name, $type, $label, $validationRules, $errorHandlers, $meta);
    }

    /**
     * Generate HTML output
     *
     * See {@link \ValidFormBuilder\Element::toHtml()}
     *
     * @internal
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        $strOutput = "";

        if ($this->__dynamic) {
            $intDynamicCount = $this->getDynamicCount();
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strOutput .= $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors, $intCount);
            }
        } else {
            $strOutput = $this->__toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
        }

        return $strOutput;
    }

    /**
     * Generate HTML output
     *
     * See {@link \ValidFormBuilder\Element::__toHtml()}
     *
     * @internal
     * @see \ValidFormBuilder\Element::__toHtml()
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true, $intCount = 0)
    {
        $strOutput = "";

        $blnError = ($submitted && ! $this->__validator->validate($intCount) && $blnDisplayErrors) ? true : false;

        if (! $blnSimpleLayout) {
            // *** We asume that all dynamic fields greater than 0 are never required.
            if ($this->__validator->getRequired()) {
                $this->setMeta("class", "vf__required");
            } else {
                $this->setMeta("class", "vf__optional");
            }

            // *** Set custom meta.
            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            if (! $blnLabel) {
                $this->setMeta("class", "vf__nolabel");
            }

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";

            if ($blnError) {
                $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
            }

            if ($blnLabel) {
                $strLabel = (! empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
                if (! empty($this->__label)) {
                    $strOutput .= "<label{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
                }
            }
        } else {
            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            $this->setMeta("class", "vf__multifielditem");

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}\">\n";
        }

        $strOutput .= "<fieldset{$this->__getFieldMetaString()}>\n";

        foreach ($this->__fields as $objField) {
            switch (get_class($objField)) {
                case "ValidFormBuilder\\GroupField":
                    $strOutput .= $objField->toHtmlInternal($this->__getValue($submitted, $intCount), $submitted, $intCount);

                    break;
            }
        }

        $strOutput .= "</fieldset>\n";

        if (! empty($this->__tip)) {
            $strOutput .= "<small class=\"vf__tip\"{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        $strOutput .= "</div>\n";

        return $strOutput;
    }

    /**
     * Generate Javascript output
     *
     * See {@link \ValidFormBuilder\Element::toJS()}
     *
     * @internal
     * @see \ValidFormBuilder\Element::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";
        $strCheck = $this->__validator->getCheck();
        $strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
        $strRequired = ($this->__validator->getRequired()) ? "true" : "false";
        $intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
        $intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";

        if ($this->__dynamic || $intDynamicPosition) {
            $intDynamicCount = $this->getDynamicCount($intDynamicPosition);
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strId = ($intCount == 0) ? $this->getId() : $this->getId() . "_" . $intCount;
                $strName = ($intCount == 0) ? $this->getName() : $this->getName() . "_" . $intCount;

                // *** We asume that all dynamic fields greater than 0 are never required.
                if ($intDynamicCount > 0) {
                    $strRequired = "false";
                }

                $strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

                // *** Render the condition logic per dynamic field.
                $strOutput .= $this->conditionsToJs($intCount);
            }
        } else {
            $strOutput = "objForm.addElement('{$this->getId()}', '{$this->getName()}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs();
        }

        return $strOutput;
    }

    /**
     * Get element's ID
     *
     * This automatically strips off the [] from a checkbox ID
     *
     * @return string The element's ID
     */
    public function getId()
    {
        return (strpos($this->__id, "[]") !== false) ? str_replace("[]", "", $this->__id) : $this->__id;
    }

    /**
     * Get the element's name
     *
     * This automatically strips off the [] from a checkbox ID
     *
     * @see \ValidFormBuilder\Base::getName()
     * @return string The element's ID
     */
    public function getName($blnPlain = false)
    {
        if ($blnPlain) {
            $name = $this->__name;
        } else {
            switch ($this->__type) {
                case ValidForm::VFORM_RADIO_LIST:
                    $name = $this->__name;
                    break;
                case ValidForm::VFORM_CHECK_LIST:
                    $name = (strpos($this->__name, "[]") === false) ? $this->__name . "[]" : $this->__name;
                    break;
            }
        }

        return $name;
    }

    /**
     * Add either a radio button or checkbox to the group
     *
     * @param string $label The label
     * @param value $value The value
     * @param boolean $checked Set to true if this item should be checked / selected by default
     * @param array $meta The meta array
     *
     * @return \ValidFormBuilder\GroupField
     */
    public function addField($label, $value, $checked = false, $meta = array())
    {
        $name = $this->getName();

        $objField = new GroupField($this->getRandomId($name), $name, $this->__type, $label, $value, $checked, $meta);
        $objField->setMeta("parent", $this, true);

        $this->__fields->addObject($objField);

        // *** Set the default value if "checked" is set.
		if ($checked) {
		    switch ($this->__type) {
		        case ValidForm::VFORM_CHECK_LIST:
		            $arrDefault = (is_array($this->__default)) ? $this->__default : array($this->__default);
		            $arrDefault[] = $value;

		            $this->__default = $arrDefault;

		            break;
		        default:
		            $this->__default = $value;
		    }
		}

        return $objField;
    }
}
