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
 * Hidden Class
 *
 * See {@see \ValidFormBuilder\ValidForm::addHiddenField()}
 *
 * #### Example; Add a hidden field to the form
 * ```php
 * $objForm->addHiddenField("secret-stuff", ValidForm::VFORM_STRING);
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
 */
class Hidden extends Element
{
    /**
     * Create new instance
     *
     * @param string $name The field's name
     * @param integer $type The type is used to validate the hidden field's value
     * @param array $meta The meta array
     */
    public function __construct($name, $type, $meta = array())
    {
        parent::__construct($name, $type, "", array(), array(), $meta);
    }

    /**
     * Generate HTML
     *
     * See {@see \ValidFormBuilder\Element::toHtml()}
     *
     * @see \ValidFormBuilder\Element::toHtml()
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true)
    {
        $strOutput = "";
        $this->setConditionalMeta();

        $strValue = $this->__getValue($submitted);
        $strValue = htmlspecialchars((string)$strValue, ENT_QUOTES);

        $strOutput .= "<input type=\"hidden\" value=\"{$strValue}\" name=\"{$this->__name}\" id=\"{$this->__id}\" {$this->__getFieldMetaString()} />\n";

        return $strOutput;
    }

    /**
     * Generate Javascript
     *
     * See {@see \ValidFormBuilder\Element::toJS()}
     *
     * @see \ValidFormBuilder\Element::toJS()
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        // *** Condition logic.
        $strOutput .= $this->conditionsToJs($intDynamicPosition);

        return $strOutput;
    }

    /**
     * Check if field contains child elements
     *
     * Always retuns false for Hidden objects
     * See {@see \ValidFormBuilder\Element::hasFields()}
     * @see \ValidFormBuilder\Element::hasFields()
     */
    public function hasFields()
    {
        return false;
    }

    /**
     * Check if this hidden field is a dynamic counter
     *
     * See {@see \ValidFormBuilder\Element::isDynamicCounter()}
     * @see \ValidFormBuilder\Element::isDynamicCounter()
     */
    public function isDynamicCounter()
    {
        return $this->__dynamiccounter;
    }

    /**
     * Validate this field
     *
     * See {@see \ValidFormBuilder\Element::isValid()}
     *
     * @see \ValidFormBuilder\Element::isValid()
     */
    public function isValid($intCount = null)
    {
        $blnReturn = false;
        $intDynamicCount = ($this->isDynamicCounter()) ? $this->__validator->getValue() : 0;

        for ($intCount = 0; $intCount <= $intDynamicCount; $intCount ++) {
            $blnReturn = $this->__validator->validate($intCount);

            if (! $blnReturn) {
                break;
            }
        }

        return $blnReturn;
    }
}
