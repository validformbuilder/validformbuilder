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
 * Navigation Class
 *
 * @internal
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 */
class Navigation extends Base
{
    /**
     * Internal fields collection
     * @internal
     * @var \ValidFormBuilder\Collection
     */
    protected $__fields;

    /**
     * Construct new Navigation object
     *
     * @internal
     * @param array $meta The meta array
     */
    public function __construct($meta = array())
    {
        $this->__meta = $meta;
        $this->__initializeMeta();

        $this->__fields = new Collection();
    }

    /**
     * Add a button to the navigation object
     *
     * @param string $label Button label
     * @param array $meta The meta array
     * @return \ValidFormBuilder\Button
     */
    public function addButton($label, $meta = array())
    {
        $objButton = new Button($label, $meta);
        // *** Set the parent for the new field.
        $objButton->setMeta("parent", $this, true);

        $this->__fields->addObject($objButton);

        return $objButton;
    }

    /**
     * Inject HTML in the navigation element
     *
     * @param string $html The HTML string
     * @return \ValidFormBuilder\String
     */
    public function addHtml($html)
    {
        $objString = new String($html);
        $objString->setMeta("parent", $this, true);
        $this->__fields->addObject($objString);

        return $objString;
    }

    /**
     * Render the Navigation and it's children
     *
     * @internal
     * @param boolean $submitted Define if the element has been submitted and propagate that flag to the child fields
     * @param boolean $blnSimpleLayout Only render in simple layout mode
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors Display generated errors
     * @return string Rendered Navigation
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true)
    {
        $this->setConditionalMeta();

        $this->setMeta("class", "vf__navigation vf__cf");
        $strReturn = "<div{$this->__getMetaString()}>\n";

        foreach ($this->__fields as $field) {
            $strReturn .= $field->toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayError);
        }

        $strReturn .= "</div>\n";

        return $strReturn;
    }

    /**
     * Generate Javascript code.
     *
     * See {@link \ValidFormBuilder\Base::toJs() Base::toJs()}
     *
     * @internal
     * @param $intDynamicPosition The dynamic position counter
     * @return string Generated javascript code
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strReturn = "";

        foreach ($this->__fields as $field) {
            $strReturn .= $field->toJS($intDynamicPosition);
        }

        return $strReturn;
    }

    /**
     * Check if element is valid
     *
     * @internal
     * @return boolean
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Get the internal fields collection
     * @internal
     * @return \ValidFormBuilder\Collection
     */
    public function getFields()
    {
        return $this->__fields;
    }

    /**
     * Get element's value
     * @internal
     * @return boolean
     */
    public function getValue()
    {
        return true;
    }

    /**
     * Get element's ID
     * @internal
     * @return null
     */
    public function getId()
    {
        return null;
    }

    /**
     * Check if element is dynamic
     * @internal
     * @return boolean
     */
    public function isDynamic()
    {
        return false;
    }

    /**
     * Get element type
     * @internal
     * @return integer
     */
    public function getType()
    {
        return 0;
    }

    /**
     * Get header
     * @internal
     * @return void
     */
    public function getHeader()
    {
        return;
    }

    /**
     * Check if element contains child elements
     * @internal
     * @return boolean
     */
    public function hasFields()
    {
        return ($this->__fields->count() > 0) ? true : false;
    }
}
