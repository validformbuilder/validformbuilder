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
 * Base class is the parent class for all ValidForm objects
 *
 * All ValidForm classes share this base class' logic.
 *
 * @package ValidForm
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 */
class Base extends ClassDynamic
{
    /**
     * The ID for this instance
     * @internal
     * @var string
     */
    protected $__id;

    /**
     * The name for this object
     * @internal
     * @var string
     */
    protected $__name;

    /**
     * A reference to the parent object
     * @internal
     * @var Base
     */
    protected $__parent;

    /**
     * An array of Condition objects if conditions are added
     * @internal
     * @var array
     */
    protected $__conditions = array();

    /**
     * The meta array
     * @internal
     * @var array
     */
    protected $__meta = array();

    /**
     * Field specific meta array
     * @internal
     * @var array
     */
    protected $__fieldmeta = array();

    /**
     * Label specific meta array
     * @internal
     * @var array
     */
    protected $__labelmeta = array();

    /**
     * Tip specific meta array
     * @internal
     * @var array
     */
    protected $__tipmeta = array();

    /**
     * Predefiend magic meta prefixes
     * @internal
     * @var array
     */
    protected $__magicmeta = array(
        "label",
        "field",
        "tip"
    );

    /**
     * Reserved meta keys
     * @internal
     * @var array
     */
    protected $__magicreservedmeta = array(
        "labelRange",
        "tip"
    );

    /**
     * Reserved field meta keys
     * @internal
     * @var array
     */
    protected $__reservedfieldmeta = array(
        "multiple",
        "rows",
        "cols"
    );

    /**
     * Reserved label meta keys
     * @internal
     * @var array
     */
    protected $__reservedlabelmeta = array();

    /**
     * Reserved general meta keys
     * @internal
     * @var array
     */
    protected $__reservedmeta = array(
        "parent",
        "data",
        "dynamicCounter",
        "tip",
        "hint",
        "default",
        "width",
        "height",
        "length",
        "start",
        "end",
        "path",
        "labelStyle",
        "labelClass",
        "labelRange",
        "fieldStyle",
        "fieldClass",
        "tipStyle",
        "tipClass",
        "valueRange",
        "dynamic",
        "dynamicLabel",
        "matchWith",
        "uniqueId",
        "sanitize"
    );

    /**
     * Get a collection of fields and look for dynamic counters recursively
     * @internal
     * @param Collection $objFields
     * @param Collection $objCollection
     * @return Collection
     */
    protected function getCountersRecursive($objFields, $objCollection = null)
    {
        if (is_null($objCollection)) {
            $objCollection = new Collection();
        }

        foreach ($objFields as $objField) {
            if ($objField instanceof Element && $objField->isDynamicCounter()) {
                $objCollection->addObject($objField);
            }

            if ($objField->hasFields()) {
                $this->getCountersRecursive($objField->getFields(), $objCollection);
            }
        }

        return $objCollection;
    }

    /**
     * Add a new condition to the current field
     *
     * For examples, check {@link \ValidFormBuilder\Condition}
     *
     * @param string $strType Define the condition type. This can be either `required`, `visibile` or `enabled`
     * @param boolean $blnValue Define whether this condition activates if the comparison(s) are true or false.
     * @param array $arrComparisons An array of Comparison objects
     * @param integer $intComparisonType The comparison type.
     * Either `ValidForm::VFORM_MATCH_ANY` or `ValidForm::VFORM_MATCH_ALL`. With `VFORM_MATCH_ANY`,
     * as soon as one of the comparisons validates the condition, the condition is enforced.
     * With `ValidForm::VFORM_MATCH_ALL`, all of the comparisons must validate before the condition will be enforced.
     *
     * @throws \Exception if Condition could not be set
     * @throws \InvalidArgumentException If invalid arguments are supplied
     */
    public function addCondition($strType, $blnValue, $arrComparisons, $intComparisonType = ValidForm::VFORM_MATCH_ANY)
    {
        if ($this->hasCondition($strType)) {
            // Get an existing condition if it's already there.
            $objCondition = $this->getCondition($strType);
        } else {
            // Add a new one if this condition type doesn't exist yet.
            $objCondition = new Condition($this, $strType, $blnValue, $intComparisonType);
        }

        if (is_array($arrComparisons) && count($arrComparisons) > 0) {
            /* @var $varComparison Array|Comparison */
            foreach ($arrComparisons as $varComparison) {
                if (is_array($varComparison) || get_class($varComparison) === "ValidFormBuilder\\Comparison") {
                    try {
                        $objCondition->addComparison($varComparison);
                    } catch (\InvalidArgumentException $e) {
                        throw new \Exception("Could not set condition: " . $e->getMessage(), 1);
                    }
                } else {
                    throw new \InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
                }
            }

            array_push($this->__conditions, $objCondition);
        } else {
            throw new \InvalidArgumentException("Invalid or no comparison(s) supplied.", 1);
        }
    }

    /**
     * Get the conditions collection
     * @internal
     * @return array
     */
    public function getConditions()
    {
        return $this->__conditions;
    }

    /**
     * Get element's Condition object
     *
     * Note: When chaining methods, always use hasCondition() first before chaining
     * for example `getCondition()->isMet()`.
     *
     * @internal
     * @param string $strProperty Condition type e.g. 'required', 'visibile' and 'enabled'
     * @return Condition|null Found condition or null if no condition is found.
     */
    public function getCondition($strProperty)
    {
        $objReturn = null;

        $objConditions = $this->getConditions();
        foreach ($objConditions as $objCondition) {
            if ($objCondition->getProperty() === strtolower($strProperty)) {
                $objReturn = $objCondition;
                break;
            }
        }

        if (is_null($objReturn) && is_object($this->__parent)) {
            // *** Find condition in parent.
            $objReturn = $this->__parent->getCondition($strProperty);
        }

        return $objReturn;
    }

    /**
     * Only get a condition of a given type if that condition is met. If the condition is not met, this returns null
     * @param string $strProperty Condition type e.g. 'required', 'visibile' and 'enabled'
     *
     * @internal
     * @return null|Condition
     */
    public function getMetCondition($strProperty)
    {
        $objReturn = null;

        $objConditions = $this->getConditions();
        foreach ($objConditions as $objCondition) {
            if ($objCondition->getProperty() === strtolower($strProperty) && $objCondition->isMet()) {
                $objReturn = $objCondition;
                break;
            }
        }

        // *** This if statement should be deprecated soon. It seems as if
        // *** some logic is still depending on this at the moment.
        if (is_null($objReturn) && is_object($this->__parent)) {
            // *** Find condition in parent.
            $objReturn = $this->__parent->getMetCondition($strProperty);
        }

        return $objReturn;
    }

    /**
     * Check if the current field contains a condition object of a specific type
     *
     * @internal
     * @param string $strProperty Condition type e.g. `required`, `visibile` and `enabled`
     * @return boolean True if element has condition object set, false if not
     */
    public function hasCondition($strProperty)
    {
        $blnReturn = false;

        foreach ($this->__conditions as $objCondition) {
            if ($objCondition->getProperty() === strtolower($strProperty)) {
                $blnReturn = true;
                break;
            }
        }

        return $blnReturn;
    }

    /**
     * Check if the current object contains any conditions at all.
     * @internal
     * @return boolean True if it contains conditions, false if not.
     */
    public function hasConditions()
    {
        return (count($this->__conditions) > 0);
    }

    /**
     * This gets the condition of a given property, just like {@link \ValidFormBuilder\Base::getCondition()}.
     * When no condition is found on the current element, the method searches for a condition in it's parent element.
     *
     * @internal
     * @param string $strProperty Condition type e.g. `required`, `visibile` and `enabled`
     * @param \ValidFormBuilder\Element $objContext
     * @return \ValidFormBuilder\Condition|null
     */
    public function getConditionRecursive($strProperty, $objContext = null)
    {
        $objReturn = null;
        $objContext = (is_null($objContext)) ? $this : $objContext;

        $objCondition = $objContext->getCondition($strProperty);

        if (! is_object($objCondition)) {
            // Go look for a condition at this element's parent.
            $objParent = $objContext->getMeta("parent", null);

            if (! is_null($objParent)) {
                $objReturn = $objContext->getConditionRecursive($strProperty, $objParent);
            }
        } else {
            $objReturn = $objCondition;
        }

        return $objReturn;
    }

    /**
     * This method determines wheter or not to show the 'add extra field' dynamic button
     * based on it's parent's condition state.
     *
     * @internal
     */
    public function getDynamicButtonMeta()
    {
        $strReturn = "";

        $objCondition = $this->getCondition("visible");
        if (is_object($objCondition)) {
            $blnResult = $objCondition->isMet();

            // This can be applied on all sorts of subjects.
            if ($blnResult) {
                if (! $objCondition->getValue()) {
                    $strReturn = " style=\"display:none;\"";
                }
            } else {
                if ($objCondition->getValue()) {
                    $strReturn = " style=\"display:none;\"";
                }
            }
        }

        return $strReturn;
    }

    /**
     * Based on which conditions are met, corresponding metadata is set on the object.
     *
     * @internal
     */
    public function setConditionalMeta()
    {
        foreach ($this->__conditions as $objCondition) {
            $blnResult = $objCondition->isMet();

            switch ($objCondition->getProperty()) {
                case "visible":
                    // This can be applied on all sorts of subjects.
                    if ($blnResult) {
                        if ($objCondition->getValue()) {
                            $this->setMeta("style", "display: block;");
                        } else {
                            $this->setMeta("style", "display: none;");
                        }
                    } else {
                        if ($objCondition->getValue()) {
                            $this->setMeta("style", "display: none;");
                        } else {
                            $this->setMeta("style", "display: block;");
                        }
                    }
                // Continueing to the required property.
                case "required":
                    // This can only be applied on all subjects except for Paragraphs
                    if (get_class($objCondition->getSubject()) !== "ValidFormBuilder\\Paragraph") {

                        if ($blnResult) {
                            if ($objCondition->getValue()) {
                                if (get_class($this) !== "ValidFormBuilder\\Fieldset") {
                                    // TODO Disabled because it messes up multifields.
                                    // $this->setMeta("class", "vf__required", true);
                                }
                            } else {
                                if (get_class($this) !== "ValidFormBuilder\\Fieldset") {
                                    // TODO Disabled because it messes up multifields.
                                    // $this->setMeta("class", "vf__optional", true);
                                }
                            }
                        } else {
                            if ($objCondition->getValue()) {
                                if (get_class($this) !== "ValidFormBuilder\\Fieldset") {
                                    // TODO Disabled because it messes up multifields.
                                    // $this->setMeta("class", "vf__optional", true);
                                }
                            } else {
                                if (get_class($this) !== "ValidFormBuilder\\Fieldset") {
                                    // TODO Disabled because it messes up multifields.
                                    // $this->setMeta("class", "vf__required", true);
                                }
                            }
                        }
                    }
                    break;

                case "enabled":
                    // This can only be applied on all subjects except for Paragraphs
                    if (get_class($objCondition->getSubject()) !== "ValidFormBuilder\\Paragraph") {

                        if ($blnResult) {
                            if ($objCondition->getValue()) {
                                $this->setFieldMeta("disabled", "", true);
                            } else {
                                $this->setFieldMeta("disabled", "disabled", true);
                            }
                        } else {
                            if ($objCondition->getValue()) {
                                $this->setFieldMeta("disabled", "disabled", true);
                            } else {
                                $this->setFieldMeta("disabled", "", true);
                            }
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Set meta property.
     *
     * @param string $property Property name.
     * @param mixed $value Property value.
     * @param boolean $blnOverwrite Optionally use this boolean to force an overwrite of previous property value.
     */
    public function setMeta($property, $value, $blnOverwrite = false)
    {
        return $this->__setMeta($property, $value, $blnOverwrite);
    }

    /**
     * Set field specific meta data
     * @param string $property Property name.
     * @param mixed $value Property value.
     * @param boolean $blnOverwrite Optionally use this boolean to force an overwrite of previous property value.
     * @return mixed The newly set value
     */
    public function setFieldMeta($property, $value, $blnOverwrite = false)
    {
        return $this->__setMeta("field" . $property, $value, $blnOverwrite);
    }

    /**
     * Get field meta property.
     *
     * @param string $property Property to get from internal field meta array.
     * @param string $fallbackValue Optional fallback value if no value is found for requested property
     * @return mixed
     */
    public function getFieldMeta($property = null, $fallbackValue = "")
    {
        if (is_null($property)) {
            $varReturn = $this->__fieldmeta;
        } elseif (isset($this->__fieldmeta[$property])
            && !is_null($this->__fieldmeta[$property])
        ) {
            $varReturn = $this->__fieldmeta[$property];
        } else {
            $varReturn = $fallbackValue;
        }

        return $varReturn;
    }

    /**
     * Set label specific meta data
     * @param string $property Property name.
     * @param mixed $value Property value.
     * @param boolean $blnOverwrite Optionally use this boolean to force an overwrite of previous property value.
     * @return mixed The newly set value
     */
    public function setLabelMeta($property, $value, $blnOverwrite = false)
    {
        return $this->__setMeta("label" . $property, $value, $blnOverwrite);
    }

    /**
     * Set tip specific meta data
     * @param string $property Property name.
     * @param mixed $value Property value.
     * @param boolean $blnOverwrite Optionally use this boolean to force an overwrite of previous property value.
     * @return mixed The newly set value
     */
    public function setTipMeta($property, $value, $blnOverwrite = false)
    {
        return $this->__setMeta("tip" . $property, $value, $blnOverwrite);
    }

    /**
     *
     * @return string Property value or empty string of none is set.
     */

    /**
     * Get meta property.
     *
     * @param string $property Property to get from internal meta array.
     * @param string $fallbackValue Optional fallback value if requested property has no value
     * @return mixed
     */
    public function getMeta($property = null, $fallbackValue = "")
    {
        if (is_null($property)) {
            $varReturn = $this->__meta;
        } elseif (isset($this->__meta[$property])
            && !is_null($this->__meta[$property])
        ) {
            $varReturn = $this->__meta[$property];
        } else {
            $varReturn = $fallbackValue;
        }

        return $varReturn;
    }

    /**
     * Get label meta property.
     *
     * @param string $property Property to get from internal label meta array.
     * @param string $fallbackValue Optional fallback value if requested property has no value
     * @return string Property value or empty string of none is set.
     */
    public function getLabelMeta($property = null, $fallbackValue = "")
    {
        if (is_null($property)) {
            $varReturn = $this->__labelmeta;
        } elseif (isset($this->__labelmeta[$property])
            && !is_null($this->__labelmeta[$property])
        ) {
            $varReturn = $this->__labelmeta[$property];
        } else {
            $varReturn = $fallbackValue;
        }

        return $varReturn;
    }

	/**
	 * Return the (original) name of the current field.
	 *
	 * Use getDynamicName() to get the field name + dynamic count
	 *
	 * @return string The original field name
	 */
	public function getName()
	{
		$strName = parent::getName();
		if (empty($strName)) {
			$strName = $this->__name = $this->__generateName();
		}

		return $strName;
	}

	/**
	 * Same as getName() except getDynamicName adds the current dynamic count to the fieldname as a suffix (_1, _2 etc)
	 *
	 * When the dynamic count === 0, the return value equals the output of getName()
	 *
     * @internal
	 * @param integer $intCount The dynamic count
	 * @return string The field name
	 */
	public function getDynamicName($intCount = 0)
	{
	    $strName = $this->getName();

	    if ($intCount > 0) {
	        $strName = $strName . "_" . $intCount;
	    }

	    return $strName;
	}

    /**
     * Get the short label (meta 'summaryLabel') if available.
     * Use the 'long' (regular)
     * label as a fallback return value.
     *
     * @internal
     * @return string The short or regular element label
     */
    public function getShortLabel()
    {
        $strReturn = $this->getLabel();
        $strShortLabel = $this->getMeta("summaryLabel", null);

        if (! is_null($strShortLabel) && strlen($strShortLabel) > 0) {
            $strReturn = $strShortLabel;
        }

        return $strReturn;
    }

    /**
     * Generate corresponding javascript code for this element
     *
     * Should be extended by child classes.
     *
     * @param integer $intDynamicPosition Dynamic position
     * @return string
     */
    public function toJS($intDynamicPosition = 0)
    {
        return "";
    }

    /**
     * Generates needed javascript initialization code for client-side conditional logic
     * @internal
     * @param integer $intDynamicPosition Dynamic position
     * @return string Generated javascript code
     */
    protected function conditionsToJs($intDynamicPosition = 0)
    {
        $strReturn = "";

        if ($this->hasConditions() && (count($this->getConditions() > 0))) {
            foreach ($this->getConditions() as $objCondition) {
                $strReturn .= "objForm.addCondition(" . json_encode($objCondition->jsonSerialize($intDynamicPosition)) . ");\n";
            }
        }

        return $strReturn;
    }

    /**
     * Generate matchWith javascript code
     * @internal
     * @param integer $intDynamicPosition
     * @return string Generated javascript
     */
    protected function matchWithToJs($intDynamicPosition = 0)
    {
        $strReturn = "";

        $objMatchWith = $this->getValidator()->getMatchWith();
        if (is_object($objMatchWith)) {
            $strId = ($intDynamicPosition == 0) ? $this->__id : $this->__id . "_" . $intDynamicPosition;
            $strMatchId = ($intDynamicPosition == 0) ? $objMatchWith->getId() : $objMatchWith->getId() . "_" . $intDynamicPosition;
            $strReturn = "objForm.matchfields('{$strId}', '{$strMatchId}', '" . $this->__validator->getMatchWithError() . "');\n";
        }

        return $strReturn;
    }

    /**
     * Store data in the current object.
     *
     * This data will not be visibile in any output and will only be used for internal purposes. For example, you
     * can store some custom data from your CMS or an other library in a field object, for later use.
     *
     * **Note: Using this method will overwrite any previously set data with the same key!**
     *
     * @param string $strKey The key for this storage
     * @param mixed $varValue The value to store
     * @return boolean True if set successful, false if not.
     */
    public function setData($strKey = null, $varValue = null)
    {
        $arrData = $this->getMeta("data", array());

        if (! is_null($strKey) && ! is_null($varValue)) {
            $arrData[$strKey] = $varValue;
        }

        // Set and overwrite previous value.
        $this->setMeta("data", $arrData, true);

        // Return boolean value
        return ! ! $this->getData($strKey);
    }

    /**
     * Get a value from the internal data array.
     *
     * @param string $strKey The key of the data attribute to return
     * @return mixed
     */
    public function getData($strKey = null)
    {
        $varReturn = false;
        $arrData = $this->getMeta("data", null);

        if (! is_null($arrData)) {
            if ($strKey == null) {
                $varReturn = $arrData;
            } else {
                if (isset($arrData[$strKey])) {
                    $varReturn = $arrData[$strKey];
                }
            }
        }

        return $varReturn;
    }

    /**
     * Generate unique name based on class name
     * @internal
     * @return string
     */
    protected function __generateName()
    {
        return strtolower(ValidForm::getStrippedClassName(get_class($this))) . "_" . mt_rand();
    }

    /**
     * Convert meta array to html attributes+values
     * @internal
     * @return string
     */
    protected function __getMetaString()
    {
        $strOutput = "";

        foreach ($this->__meta as $key => $value) {
            if (! in_array($key, array_merge($this->__reservedmeta, $this->__fieldmeta))) {
                $strOutput .= " {$key}=\"{$value}\"";
            }
        }

        return $strOutput;
    }

    /**
     * Convert fieldmeta array to html attributes+values
     * @internal
     * @return string
     */
    protected function __getFieldMetaString()
    {
        $strOutput = "";

        if (is_array($this->__fieldmeta)) {
            foreach ($this->__fieldmeta as $key => $value) {
                if (! in_array($key, $this->__reservedmeta)) {
                    $strOutput .= " {$key}=\"{$value}\"";
                }
            }
        }

        return $strOutput;
    }

    /**
     * Convert labelmeta array to html attributes+values
     * @internal
     * @return string
     */
    protected function __getLabelMetaString()
    {
        $strOutput = "";

        if (is_array($this->__labelmeta)) {
            foreach ($this->__labelmeta as $key => $value) {
                if (! in_array($key, $this->__reservedmeta)) {
                    $strOutput .= " {$key}=\"{$value}\"";
                }
            }
        }

        return $strOutput;
    }

    /**
     * Convert tipmeta array to html attributes+values
     * @internal
     * @return string
     */
    protected function __getTipMetaString()
    {
        $strOutput = "";

        if (is_array($this->__tipmeta)) {
            foreach ($this->__tipmeta as $key => $value) {
                if (! in_array($key, $this->__reservedmeta)) {
                    $strOutput .= " {$key}=\"{$value}\"";
                }
            }
        }

        return $strOutput;
    }

    /**
     * Filter out special field or label specific meta tags from the main
     * meta array and add them to the designated meta arrays __fieldmeta or __labelmeta.
     * Example: `$meta["labelstyle"] = "width: 20px";` will become `$__fieldmeta["style"] = "width: 20px;"`
     * Any meta key that starts with 'label' or 'field' will be assigned to it's
     * corresponding internal meta array.
     *
     * @internal
     * @return void
     */
    protected function __initializeMeta()
    {
        foreach ($this->__meta as $key => $value) {
            if (in_array($key, $this->__reservedfieldmeta)) {
                $key = "field" . $key;
            }

            if (in_array($key, $this->__reservedlabelmeta)) {
                $key = "label" . $key;
            }

            $intLength = 5;
            $strMagicKey = strtolower(substr($key, 0, $intLength));
            if (!in_array($strMagicKey, $this->__magicmeta)) {
                $intLength = 3;
                $strMagicKey = strtolower(substr($key, 0, $intLength));
            }
            if (in_array($strMagicKey, $this->__magicmeta) && ! in_array($key, $this->__magicreservedmeta)) {
                $strMethod = "set" . ucfirst($strMagicKey) . "Meta";
                $this->$strMethod(strtolower(substr($key, - (strlen($key) - $intLength))), $value);

                unset($this->__meta[$key]);
            }
        }
    }

    /**
     * Helper method to set meta data
     * @internal
     * @param string $property The key to set in the meta array
     * @param string $value The corresponding value
     * @param string $blnOverwrite If true, overwrite pre-existing key-value pair
     * @return array
     */
    protected function __setMeta($property, $value, $blnOverwrite = false)
    {
        $internalMetaArray = &$this->__meta;

        // *** Re-set internalMetaArray if property has magic key 'label', 'field' or 'tip'
        $strMagicKey = strtolower(substr($property, 0, 5));
        if (!in_array($strMagicKey, $this->__magicmeta)) {
            $strMagicKey = strtolower(substr($property, 0, 3));
        }
        if (in_array($strMagicKey, $this->__magicmeta)) {
            switch ($strMagicKey) {
                case "field":
                    $internalMetaArray = &$this->__fieldmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 5)));
                    break;
                case "label":
                    $internalMetaArray = &$this->__labelmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 5)));
                    break;
                case "tip":
                    $internalMetaArray = &$this->__tipmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 3)));
                    break;
                default:
            }
        }

        if ($blnOverwrite) {
            if (empty($value) || is_null($value)) {
                unset($internalMetaArray[$property]);
            } else {
                $internalMetaArray[$property] = $value;
            }

            return $value;
        } else {
            $varMeta = (isset($internalMetaArray[$property])) ? $internalMetaArray[$property] : "";

            // *** If the id is being set and there is already a value we don't set the new value.
            if ($property == "id" && $varMeta != "") {
                return $varMeta;
            }

            // *** Define delimiter per meta property.
            switch ($property) {
                case "style":
                    $strDelimiter = ";";
                    break;

                default:
                    $strDelimiter = " ";
            }

            // *** Add the value to the property string.
            $arrMeta = (! is_array($varMeta)) ? explode($strDelimiter, $varMeta) : $varMeta;
            $arrMeta[] = $value;

            // Make sure no empty values are left in the array.
            $arrMeta = array_filter($arrMeta);
            $varMeta = implode($strDelimiter, $arrMeta);

            $internalMetaArray[$property] = $varMeta;

            return $varMeta;
        }
    }

    /**
     * Helper method. Replaces meta data.
     * @internal
     * @param string $property
     * @param string $originalValue
     * @param string $replacement
     */
    protected function __replaceMeta($property, $originalValue, $replacement = null)
    {
        $internalMetaArray = &$this->__meta;

        // *** Re-set internalMetaArray if property has magic key 'label', 'field' or 'tip'
        $strMagicKey = strtolower(substr($property, 0, 5));
        if (!in_array($strMagicKey, $this->__magicmeta)) {
            $strMagicKey = strtolower(substr($property, 0, 3));
        }
        if (in_array($strMagicKey, $this->__magicmeta)) {
            switch ($strMagicKey) {
                case "field":
                    $internalMetaArray = &$this->__fieldmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 5)));
                    break;
                case "label":
                    $internalMetaArray = &$this->__labelmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 5)));
                    break;
                case "tip":
                    $internalMetaArray = &$this->__tipmeta;
                    $property = strtolower(substr($property, - (strlen($property) - 3)));
                    break;
                default:
            }
        }

        foreach ($internalMetaArray as $prop => $value) {
            if ($property == $prop) {
                $varMeta = (isset($internalMetaArray[$property])) ? $internalMetaArray[$property] : "";

                // *** Define delimiter per meta property.
                switch ($property) {
                    case "style":
                        $strDelimiter = ";";
                        break;

                    default:
                        $strDelimiter = " ";
                }

                // *** Add the value to the property string.
                $arrMeta = explode($strDelimiter, $varMeta);
            }
        }
    }
}
