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
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @copyright 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */

namespace ValidFormBuilder;

/**
 * Condition class
 *
 * A condition object is a set of one or more comparisons. Don't use the Condition object as a standalone, rather
 * use the element's {@link \ValidFormBuilder\Base::addCondition()} method.
 *
 * #### Example; Basic yes-no condition
 * ```php
 * $objCheck = $objForm->addField("yesno", "Yes or No", ValidForm::VFORM_RADIO_LIST);
 * $objYes = $objCheck->addField("Yes", "yes");
 * $objCheck->addField("No", "no");
 *
 * $objText = $objForm->addField(
 *     "textfield",
 *     "Text here",
 *     ValidForm::VFORM_TEXT,
 *     array("required" => "true"),
 *     array("required" => "This field is required"),
 *     array("fielddisabled" => "disabled")
 * );
 * $objText->addCondition("enabled", true, array(
 * 	new Comparison($objYes, ValidForm::VFORM_COMPARISON_EQUAL, "yes")
 * ));
 * ```
 *
 * #### Example 2; Hide field when other field has predefined value
 * ```php
 * $objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
 * $objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
 * $objLastName->addCondition(
 *     'visible', // Last name will become
 *     false, // 'not visible' (visible -> false)
 *     array(
 *         // When field $objFirstName 'is equal to' Robin
 *         new \ValidFormBuilder\Comparison($objFirstName, ValidForm::VFORM_COMPARISON_EQUAL, 'Robin')
 *     )
 * );
 * ```
 *
 * #### Example 3; Trigger condition with comparison that doesn't need a value
 * ```php
 * $objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
 * $objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
 * $objFirstName->addCondition(
 *     'enabled', // First Name will be
 *     false, // 'disabled' (enabled -> false)
 *     array(
 *         // When field $objLastName 'is not empty'
 *         // (note that we cal leave out the third 'value' parameter in this case)
 *         new \ValidFormBuilder\Comparison($objLastName, ValidForm::VFORM_COMPARISON_NOT_EMPTY)
 *     )
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class Condition extends ClassDynamic
{

    /**
     * The field element to apply the condition on. Can be any element from paragraph to textarea.
     * @internal
     * @var \ValidFormBuilder\Base
     */
    protected $__subject;

    /**
     * The property to trigger on the subject; 'enabled', 'visible' or 'required'
     * @internal
     * @var string
     */
    protected $__property;

    /**
     * The boolean to set the property with. E.g. when property is 'required' and 'value' is false, the field will
     * become optional when the condition is met.
     * @internal
     * @var boolean
     */
    protected $__value;

    /**
     * An array of \ValidFormBuilder\Comparison objects or arrays
     * @internal
     * @var array
     */
    protected $__comparisons = array();

    /**
     * Define if all comparisons should be met, `ValidForm::VFORM_MATCH_ALL`, or trigger condition if any of the
     * comparisons is met, `ValidForm::VFORM_MATCH_ANY` (default)
     * @internal
     * @var string
     */
    protected $__comparisontype;

    /**
     * Predefined condition properties
     * @internal
     * @var array
     */
    private $__conditionProperties = array(
        "visible",
        "enabled",
        "required"
    );

    /**
     * Create new Condition
     *
     * @param \ValidFormBuilder\Base $objField The target field to apply this condition on
     * @param string $strProperty The property to trigger on the subject; `enabled`, `visible` or `required`
     * @param boolean $blnValue The boolean to set the property with. E.g. when `property` is `required` and `value`
     * is `false`, the field will become optional when the condition is met.
     * @param string $strComparisonType The comparison type
     * @throws \InvalidArgumentException If `$objField` is no object or `$strProperty` is no predefined property.
     */
    public function __construct($objField, $strProperty, $blnValue = null, $strComparisonType = ValidForm::VFORM_MATCH_ANY)
    {
        $strProperty = strtolower($strProperty);

        if (!is_object($objField)) {
            throw new \InvalidArgumentException(
                "No valid object passed to Condition.",
                E_ERROR
            );
        }

        if (!in_array($strProperty, $this->__conditionProperties)) {
            throw new \InvalidArgumentException(
                "Invalid type specified in Condition constructor. Valid types are: " .
                implode(", ", $this->__conditionProperties),
                E_ERROR
            );
        }

        $this->__subject = $objField;
        $this->__property = $strProperty;
        $this->__comparisontype = $strComparisonType;
        $this->__value = $blnValue;
    }

    /**
     * Get subject value
     * @internal
     * @return \ValidFormBuilder\Base Subject element
     */
    public function getSubject()
    {
        return $this->__subject;
    }

    /**
     * Get Property
     * @internal
     * @return string
     */
    public function getProperty()
    {
        return $this->__property;
    }

    /**
     * Get value
     * @internal
     * @return boolean
     */
    public function getValue()
    {
        return $this->__value;
    }

    /**
     * Get comparisons collection
     * @internal
     * @return array
     */
    public function getComparisons()
    {
        return $this->__comparisons;
    }

    /**
     * Get comparison type
     * @internal
     * @return
     */
    public function getComparisonType()
    {
        return $this->__comparisontype;
    }

    /**
     * Add new comparison to Condition
     *
     * @internal
     * @param Comparison|array $varComparison Comparison array or Comparison object
     * @throws \Exception if Reflection couldn't initialize new Comparison object
     * @throws \InvalidArgumentException if no valid Comparison data is supplied
     */
    public function addComparison($varComparison)
    {
        $objComparison = null;

        if (is_array($varComparison)) {
            $varArguments = array_keys($varComparison);
            if (isset($varComparison["subject"])) {
                // Apparently, this is an associative array
                $varArguments = array_values($varComparison);
            }

            try {
                // @todo Replace Reflection with call_user_func_array()
                $objReflection = new \ReflectionClass("Comparison");
                $objComparison = $objReflection->newInstanceArgs($varArguments);
            } catch (\Exception $e) {
                throw new \Exception("Failed to add Comparison: " . $e->getMessage(), 1);
            }

            if (is_object($objComparison)) {
                array_push($this->__comparisons, $objComparison);
            } else {
                throw new \InvalidArgumentException("No valid comparison data supplied in addComparison() method.", 1);
            }
        } elseif (is_object($varComparison) && get_class($varComparison) === "ValidFormBuilder\\Comparison") {
            array_push($this->__comparisons, $varComparison);
        } else {
            throw new \InvalidArgumentException("No valid comparison data supplied in addComparison() method.", 1);
        }
    }

    /**
     * Verify if the condition is met
     *
     * @internal
     * @param number $intDynamicPosition Dynamic position of the field to verify
     * @return boolean True if it is met, false if not
     */
    public function isMet($intDynamicPosition = 0)
    {
        $blnResult = false;

        switch ($this->__comparisontype) {
            default:
            case ValidForm::VFORM_MATCH_ANY:
				/* @var $objComparison Comparison */
				foreach ($this->__comparisons as $objComparison) {
                    if ($objComparison->check($intDynamicPosition)) {
                        $blnResult = true; // One of the comparisons is true, that's good enough.
                        break;
                    }
                }

                break;

            case ValidForm::VFORM_MATCH_ALL:
                $blnFailed = false;
				/* @var $objComparison Comparison */
                foreach ($this->__comparisons as $objComparison) {
                    if (!$objComparison->check($intDynamicPosition)) {
                        $blnFailed = true;
                        break;
                    }
                }

                $blnResult = !$blnFailed;

                break;
        }

        return $blnResult;
    }

    /**
     * toJson method creates an array representation of the current condition object and all
     * of it's comparions.
     *
     * In the future this class should extend the JsonSerializable interface
     * (http://php.net/manual/en/class.jsonserializable.php). Since this is only
     * supported in PHP >= 5.4, we now use our own implementation.
     *
     * @internal
     * @return array An array representation of this object and it's comparisons.
     */
    public function jsonSerialize($intDynamicPosition = null)
    {
        if (get_class($this->__subject) == "ValidFormBuilder\\GroupField"
            || get_class($this->__subject) == "ValidFormBuilder\\Area"
        ) {
            $identifier = $this->__subject->getId();
        } elseif (get_class($this->__subject) == "ValidFormBuilder\\String") {
            $identifier = $this->__subject->getMeta("id");
        } else {
            $identifier = $this->__subject->getName();
            if ($intDynamicPosition > 0) {
                $identifier = $identifier . "_" . $intDynamicPosition;
            }
        }

        $arrReturn = array(
            "subject" => $identifier,
            "property" => $this->__property,
            "value" => $this->__value,
            "comparisonType" => $this->__comparisontype,
            "comparisons" => array()
        );

        foreach ($this->__comparisons as $objComparison) {
            array_push($arrReturn["comparisons"], $objComparison->jsonSerialize($intDynamicPosition));
        }

        return $arrReturn;
    }
}
