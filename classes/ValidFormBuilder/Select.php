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
 * Create a select element
 *
 * ValidForm Builder select elements support both &lt;option&gt; and &lt;optgroup&gt; elements by
 * using respectively `addField()` and `addGroup()`.
 *
 * #### Example 1; Standard way of creating a select element
 * ```php
 * $objSelect = $objForm->addField(
 *     "rating",
 *     "Rate ValidForm Builder",
 *     ValidForm::VFORM_SELECT_LIST
 * );
 * $objSelect->addField("Awesome", 1);
 * $objSelect->addField("Great", 2);
 * $objSelect->addField("Super Cool", 3, true); // This item is selected by default
 * $objSelect->addField("Splendid", 4);
 * $objSelect->addField("Best thing ever happened", 5);
 * ```
 *
 * #### Example 2; Creating options by using `labelRange` and `valueRange` options
 * ```php
 * $objForm->addField(
 *     "rating",
 *     "Rate ValidForm Builder",
 *     ValidForm::VFORM_SELECT_LIST,
 *     array(),
 *     array(),
 *     array(
 *         // An array of <option> labels
 *         "labelRange" => array(
 *             "Awesome",
 *             "Great",
 *             "Super Cool",
 *             "Splendid",
 *             "Best thing ever happened"
 *         ),
 *         // An array of corresponding <option> values
 *         "valueRange" => array(1, 2, 3, 4, 5)
 *     )
 * );
 * ```
 *
 * #### Example 3; Creating options by using `start` and `end` meta
 * ```php
 * $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST, array(), array(), array(
 * 	"start" => 1,
 * 	"end" => 5
 * ));
 * ```
 *
 * #### Example 4; Adding optgroups to the select element
 * ```php
 * $objSelect = $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST);
 * $objSelect->addGroup("Preferred rating");
 * $objSelect->addField("Awesome", 1);
 * $objSelect->addGroup("Other ratings");
 * $objSelect->addField("Great", 2);
 * $objSelect->addField("Super Cool", 3, true); // This item is selected by default
 * $objSelect->addField("Splendid", 4);
 * $objSelect->addField("Best thing ever happened", 5);
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class Select extends Element
{
    /**
     * Collection of option elements created for this select element
     * @internal
     * @var \ValidFormBuilder\Collection
     */
    protected $__options;

    /**
     * Create new Select element
     *
     * @internal
     * @param string $name Field name
     * @param integer $type Field type
     * @param string $label Field label
     * @param array $validationRules Validation rules
     * @param array $errorHandlers Error rules
     * @param array $meta The meta array
     */
    public function __construct(
        $name,
        $type,
        $label = "",
        $validationRules = array(),
        $errorHandlers = array(),
        $meta = array()
    ) {
        $this->__options = new Collection();

        parent::__construct($name, $type, $label, $validationRules, $errorHandlers, $meta);

        // Parse ranges if meta ranges are set. Thisway, the Select element is filled before
        // calling toHtml and therefore ready for custom manipulation
        if ($this->__options->count() == 0) {
            $this->__parseRanges();
        }
    }

    /**
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
     * @internal
     * @see \ValidFormBuilder\Element::__toHtml()
     */
    public function __toHtml(
        $submitted = false,
        $blnSimpleLayout = false,
        $blnLabel = true,
        $blnDisplayErrors = true,
        $intCount = 0
    ) {
        $strOutput = "";

        $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;
        $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
        $blnError = ($submitted && ! $this->__validator->validate($intCount) && $blnDisplayErrors) ? true : false;

        if (! $blnSimpleLayout) {
            // *** We asume that all dynamic fields greater than 0 are never required.
            if ($this->__validator->getRequired() && $intCount == 0) {
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

            $strOutput .= "<div{$this->__getMetaString()}>\n";

            if ($blnError) {
                $strOutput .= "<p class=\"vf__error\">{$this->__validator->getError($intCount)}</p>";
            }

            $strLabel = (! empty($this->__requiredstyle) && $this->__validator->getRequired()) ? sprintf($this->__requiredstyle, $this->__label) : $this->__label;
            if (! empty($this->__label)) {
                $strOutput .= "<label for=\"{$strId}\"{$this->__getLabelMetaString()}>{$strLabel}</label>\n";
            }
        } else {
            if ($blnError) {
                $this->setMeta("class", "vf__error");
            }

            $this->setMeta("class", "vf__multifielditem");

            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = "<div{$this->__getMetaString()}>\n";
        }

        $strOutput .= "<select name=\"{$strName}\" id=\"{$strId}\" {$this->__getFieldMetaString()}>\n";

        // *** If no option elements are available, parse ranges
        if ($this->__options->count() == 0) {
            $this->__parseRanges();
        }

        foreach ($this->__options as $option) {
            $strOutput .= $option->toHtmlInternal($this->__getValue($submitted, $intCount));
        }

        $strOutput .= "</select>\n";

        if ($this->getMeta("tip") !== "") {
            $this->__tip = $this->getMeta("tip");
        }

        if (! empty($this->__tip)) {
            $strOutput .= "<small class=\"vf__tip\"{$this->__getTipMetaString()}>{$this->__tip}</small>\n";
        }

        $strOutput .= "</div>\n";

        if (! $blnSimpleLayout && $intCount == $this->getDynamicCount()) {
            $strOutput .= $this->__addDynamicHtml();
        }

        return $strOutput;
    }

    /**
     * Generate ranges
     *
     * Supported meta keys for ranges:
     *
     * - labelRange
     * - valueRange
     * - start
     * - end
     *
     * @internal
     */
    protected function __parseRanges()
    {
        if (isset($this->__meta["labelRange"]) && is_array($this->__meta["labelRange"])) {
            if (isset($this->__meta["valueRange"]) && is_array($this->__meta["valueRange"]) && count($this->__meta["labelRange"]) == count($this->__meta["valueRange"])) {
                $intIndex = 0;
                foreach ($this->__meta["labelRange"] as $strLabel) {
                    $this->addField($strLabel, $this->__meta["valueRange"][$intIndex]);
                    $intIndex ++;
                }
            } else {
                foreach ($this->__meta["labelRange"] as $strLabel) {
                    $this->addField($strLabel, $strLabel);
                }
            }
        } elseif (isset($this->__meta["start"]) && is_numeric($this->__meta["start"]) && isset($this->__meta["end"]) && is_numeric($this->__meta["end"])) {
            if ($this->__meta["start"] < $this->__meta["end"]) {
                for ($intIndex = $this->__meta["start"]; $intIndex <= $this->__meta["end"]; $intIndex ++) {
                    $this->addField($intIndex, $intIndex);
                }
            } else {
                for ($intIndex = $this->__meta["start"]; $intIndex >= $this->__meta["end"]; $intIndex --) {
                    $this->addField($intIndex, $intIndex);
                }
            }
        }
    }

    /**
     * Render html element needed for dynamic duplication client-side
     * @internal
     * @return string
     */
    protected function __addDynamicHtml()
    {
        $strReturn = "";

        if ($this->__dynamic && ! empty($this->__dynamicLabel)) {
            $strReturn = "<div class=\"vf__dynamic vf__cf\"><a href=\"#\" data-target-id=\"{$this->__id}\" data-target-name=\"{$this->__name}\">{$this->__dynamicLabel}</a></div>\n";
        }

        return $strReturn;
    }

    /**
     * Render javascript
     * @internal
     * @see \ValidFormBuilder\Element::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strCheck = $this->__validator->getCheck();
        $strCheck = (empty($strCheck)) ? "''" : str_replace("'", "\\'", $strCheck);
        $strRequired = ($this->__validator->getRequired()) ? "true" : "false";
        $intMaxLength = ($this->__validator->getMaxLength() > 0) ? $this->__validator->getMaxLength() : "null";
        $intMinLength = ($this->__validator->getMinLength() > 0) ? $this->__validator->getMinLength() : "null";
        $strOutput = "";

        if ($this->__dynamic || $intDynamicPosition) {
            $intDynamicCount = $this->getDynamicCount($intDynamicPosition);
            for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
                $strId = ($intCount == 0) ? $this->__id : $this->__id . "_" . $intCount;
                $strName = ($intCount == 0) ? $this->__name : $this->__name . "_" . $intCount;

                // *** We asume that all dynamic fields greater than 0 are never required.
                if ($intDynamicCount > 0) {
                    $strRequired = "false";
                }

                $strOutput .= "objForm.addElement('{$strId}', '{$strName}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

                // *** Render the condition logic per dynamic field.
                $strOutput .= $this->conditionsToJs($intCount);
            }
        } else {
            $strOutput = "objForm.addElement('{$this->__id}', '{$this->__name}', {$strCheck}, {$strRequired}, {$intMaxLength}, {$intMinLength}, '" . addslashes($this->__validator->getFieldHint()) . "', '" . addslashes($this->__validator->getTypeError()) . "', '" . addslashes($this->__validator->getRequiredError()) . "', '" . addslashes($this->__validator->getHintError()) . "', '" . addslashes($this->__validator->getMinLengthError()) . "', '" . addslashes($this->__validator->getMaxLengthError()) . "');\n";

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs();
        }

        return $strOutput;
    }

    /**
     * Add option element
     *
     * @param string $label The option elements label
     * @param string $label The option elements value
     * @param boolean $selected True if this option should be selected by default
     * @param array $meta The meta array
     * @return \ValidFormBuilder\SelectOption
     */
    public function addField($label, $value, $selected = false, $meta = array())
    {
        $objOption = new SelectOption($label, $value, $selected, $meta);
        $objOption->setMeta("parent", $this, true);

        $this->__options->addObject($objOption);

        return $objOption;
    }

    /**
     * Add optgroup element
     *
     * @param string $label The optgroup's label
     * @return \ValidFormBuilder\SelectGroup
     */
    public function addGroup($label)
    {
        $objGroup = new SelectGroup($label);
        $objGroup->setMeta("parent", $this, true);

        $this->__options->addObject($objGroup);

        return $objGroup;
    }
}
