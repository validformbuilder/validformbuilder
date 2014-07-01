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
 * Button Class
 *
 * This generates a &lt;button&gt; element. You can customize this button using the meta array.
 * For example, you can add a custom class property to the button like this:
 *
 * ```php
 * $objForm->addButton(
 *     "Button label",
 *     array(
 *         // Set for example a Twitter Bootstrap class on this button
 *         "fieldclass" => "btn btn-large"
 *     )
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 *
 * @method string getId() getId() Returns the ID of the Button object
 * @method string setId() setId($strId) Overwrites the ID of the Button object
 * @method string getLabel() getLabel() Returns the label of the Button object
 * @method string setLabel() setLabel($strLabel) Overwrites the label of the Button object
 * @method string getType() getType() Returns the type of the Button object
 * @method string setType() setType($strButtonType = 'submit') Overwrites the type of the Button object.
 * Defaults to 'submit'
 *
 */
class Button extends Base
{

    /**
     * Button ID
     * @internal
     * @var string
     */
    protected $__id;
    /**
     * Button label
     * @internal
     * @var string
     */
    protected $__label;
    /**
     * Button type - either 'submit' or 'button'
     * @internal
     * @var string
     */
    protected $__type;

    /**
     * Create a new Button instance
     * @param string $label The button's label
     * @param array $meta The meta array
     */
    public function __construct($label, $meta = array())
    {
        $this->__label = $label;
        $this->__meta = $meta;
        $this->__type = (isset($meta["type"])) ? $meta["type"] : "submit";
        $this->__id = $this->__generateId();

        $this->setFieldMeta("class", "vf__button");

        // *** Set label & field specific meta
        $this->__initializeMeta();

        $strFieldId = $this->getFieldMeta("id", null);
        if (is_null($strFieldId)) {
            $this->setFieldMeta("id", $this->__id);
        }
    }

    /**
     * Generate the HTML output for this button
     *
     * @param boolean $submitted Obsolete property only used to keep method fingerprint compatible
     * @param boolean $blnSimpleLayout Obsolete property only used to keep method fingerprint compatible
     * @param boolean $blnLabel Obsolete property only used to keep method fingerprint compatible
     * @param boolean $blnDisplayErrors Obsolete property only used to keep method fingerprint compatible
     *
     * @return string Generated HTML output
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        if (! empty($this->__disabled)) {
            $this->setFieldMeta("disabled", "disabled");
        }

        $strReturn = "<input type=\"{$this->__type}\" value=\"{$this->__label}\"{$this->__getFieldMetaString()} />\n";

        return $strReturn;
    }

    /**
     * Validate this button
     * @return boolean Always true; buttons are always valid
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Check if this is a dynamic element
     * @return boolean Always false; buttons can't be dynamic
     */
    public function isDynamic()
    {
        return false;
    }

    /**
     * Check if this element has child elements.
     * @return boolean Always false. Buttons can't have child elements
     */
    public function hasFields()
    {
        return false;
    }

    /**
     * Generate a unique ID based on the class name and a random integer.
     * @internal
     * @return string The generted Unique ID
     */
    private function __generateId()
    {
        return strtolower(ValidForm::getStrippedClassName(get_class($this))) . "_" . mt_rand();
    }
}
