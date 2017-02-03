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
 * Paragraph Class
 *
 * #### Example; Add a paragraph to a form
 * ```php
 * $objForm->addField("cool-field", "Any field", ValidForm::VFORM_STRING);
 * $objForm->addParagraph("This is such a cool paragraph. It even has a title: ", "Cool title!");
 * ```
 *
 * @internal
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 */
class Paragraph extends Base
{

    /**
     * Paragraph header
     * @internal
     * @var string
     */
    protected $__header;

    /**
     * Paragraph body
     * @internal
     * @var string
     */
    protected $__body;

    /**
     * Construct new Paragraph object
     *
     * @internal
     * @param string $header Paragraph title
     * @param string $body Paragraph content
     * @param array $meta The meta array
     */
    public function __construct($header = null, $body = null, $meta = array())
    {
        $this->__header = $header;
        $this->__body = $body;
        $this->__meta = $meta;

        $this->__initializeMeta();

        $this->setMeta("id", $this->getName());
    }

    /**
     * Render paragraph's HTML
     * @return string Generated HTML code
     */
    public function __toHtml()
    {
        return $this->toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true);
    }

    /**
     * Render paragraph HTML
     *
     * See {@link \ValidFormBuilder\Base::toHtml()}
     *
     * @internal
     * @param boolean $submitted
     * @param boolean $blnSimpleLayout
     * @param boolean $blnLabel
     * @param boolean $blnDisplayError
     * @return string Generated Html
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayError = true)
    {
        // Call this before __getMetaString();
        $this->setConditionalMeta();

        $this->setMeta("class", "vf__paragraph");

        $strOutput = "<div{$this->__getMetaString()}>\n";

        // Add header if not empty.
        if (! empty($this->__header)) {
            $strOutput .= "<h3{$this->__getLabelMetaString()}>{$this->__header}</h3>\n";
        }

        if (! empty($this->__body)) {
            if (preg_match("/<p.*?>/", $this->__body) > 0) {
                $strOutput .= "{$this->__body}\n";
            } else {
                $strOutput .= "<p{$this->__getFieldMetaString()}>{$this->__body}</p>\n";
            }
        }

        $strOutput .= "</div>\n";

        return $strOutput;
    }

    /**
     * Generate javascript
     *
     * See {@link \ValidFormBuilder\Base::toJS()}
     *
     * @internal
     * @see \ValidFormBuilder\Base::toJS()
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
     *
     * @internal
     * @return boolean
     */
    public function isValid()
    {
        return true;
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
     * Get element's value
     * @internal
     * @return NULL
     */
    public function getValue()
    {
        return null;
    }

    /**
     * Check if element has fields.
     *
     * Always returns false for paragraph's
     * @internal
     * @return boolean
     */
    public function hasFields()
    {
        return false;
    }

    /**
     * Return internal fields collection
     *
     * Always an empty array. Paragraphs don't have fields.
     * @internal
     * @return array
     */
    public function getFields()
    {
        return array();
    }

    /**
     * For API compatibility, we've added the placeholder method 'getType'
     * @internal
     * @return number
     */
    public function getType()
    {
        return ValidForm::VFORM_PARAGRAPH;
    }
}
