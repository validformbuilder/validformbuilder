<?php
/**
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
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
 * @copyright 2009-2017 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */
namespace ValidFormBuilder;

/**
 * Injects a string in the form.
 *
 * Use this to add an extra string in the form. For instance, you can create an input field like this:
 *
 * ```
 * Enter the amount:   $ _____
 * ```
 *
 * In this example, we used StaticText to inject the dollar sign before our input field.
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
 */
class StaticText extends Base
{
    /**
     * Element's ID
     * @var string
     */
    protected $__id;

    /**
     * StaticText contents
     * @var string
     */
    protected $__body;

    /**
     * Create new StaticText instance
     *
     * @param string $bodyString The string to inject. Can be a simple string or even HTML code.
     * @param array $meta The meta array
     */
    public function __construct($bodyString, $meta = array())
    {
        $this->__body = $bodyString;
        $this->__meta = $meta;
    }

    /**
     * Render the string's HTML
     *
     * @param boolean $submitted Force 'submitted' behavior
     * @param boolean $blnSimpleLayout Force simple layout
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false)
    {
        return $this->__toHtml($submitted, $blnSimpleLayout);
    }

    /**
     * Render the string's HTML
     * @param boolean $submitted Force 'submitted' behavior
     * @param boolean $blnSimpleLayout Force simple layout
     * @return string The generated HTML output
     */
    public function __toHtml($submitted = false, $blnSimpleLayout = false)
    {
        if (! $blnSimpleLayout) {
            // Call this right before __getMetaString();
            $this->setConditionalMeta();

            $strOutput = str_replace("[[metaString]]", $this->__getMetaString(), $this->__body);
        } else {
            $this->setMeta("class", "vf__multifielditem");
            $strOutput = "<div " . $this->__getMetaString() . "><span>{$this->__body}</span></div>\n";
        }

        return $strOutput;
    }

    /**
     * Render the string's Javascript
     * @see \ValidFormBuilder\Base::toJS()
     * @param boolean $submitted Force 'submitted' behavior
     * @param boolean $blnSimpleLayout Force simple layout
     * @return string The generated HTML output
     */
    public function toJS($intDynamicPosition = 0)
    {
        $strOutput = "";

        if ($this->getMeta("id")) {
            $strId = $this->getMeta("id");

            $strOutput = "objForm.addElement('{$strId}', '{$strId}');\n";

            // *** Condition logic.
            $strOutput .= $this->conditionsToJs($intDynamicPosition);
        }

        return $strOutput;
    }

    /**
     * Validate string
     * @return boolean
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Check if string has fields
     *
     * Always returns false, string can't contain fields
     * @return boolean False
     */
    public function hasFields()
    {
        return false;
    }

    /**
     * Get string value
     *
     * Return nothing; string has no value.
     *
     * @return void
     */
    public function getValue()
    {
        return;
    }

    /**
     * Get validator object
     *
     * Always returns null; string has no validator object
     * @return null
     */
    public function getValidator()
    {
        return null;
    }

    /**
     * Get string name
     *
     * StaticText has no name, only an ID
     * @return void
     */
    public function getName()
    {
        return;
    }

    /**
     * Get information from data array
     *
     * Not applicable for StaticText objects.
     *
     * @return void
     */
    public function getData($strKey = null)
    {
        return;
    }

    /**
     * Check if string is dynamic
     *
     * @return boolean False
     */
    public function isDynamic()
    {
        return false;
    }
}
