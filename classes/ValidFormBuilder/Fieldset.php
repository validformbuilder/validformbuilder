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
 * Fieldset Class
 *
 * Create a new fieldset to the form like this:
 * ```php
 * $objForm->addFieldset(
 *     'Great title for the fieldset',
 *     'We need a small note as well',
 *     'Note:'
 * );
 * ```
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 */
class Fieldset extends Base
{

    /**
     * Note header
     * @internal
     * @var string
     */
    protected $__header;

    /**
     * Note body
     * @internal
     * @var string
     */
    protected $__note;

    /**
     * Internal fields collection
     * @internal
     * @var Collection
     */
    protected $__fields;

    /**
     * Create a new fieldset
     * @internal
     * @param string $header Optional fieldset title
     * @param string $noteHeader Optional fieldset note block header
     * @param string $noteBody Optional fieldset note block body
     * @param array $meta The meta array
     */
    public function __construct($header = null, $noteHeader = null, $noteBody = null, $meta = array())
    {
        $this->__header = $header;
        $this->__meta = $meta;

        // *** Set label & field specific meta
        $this->__initializeMeta();

        $this->__fields = new Collection();

        if (! empty($noteHeader) || ! empty($noteBody)) {
            $this->__note = new Note($noteHeader, $noteBody);
        }
    }

    /**
     * Add an object to the fiedset's elements collection
     *
     * @param \ValidFormBuilder\Base $field The object to add
     * @throws \InvalidArgumentException if property passed to `addField()` is not an instance of Base
     */
    public function addField($field)
    {
        if (!$field instanceof Base) {
            throw new \InvalidArgumentException(
                "No valid object passed to Fieldset::addField(). " .
                "Object should be an instance of \\ValidFormBuilder\\Base.",
                E_ERROR
            );
        }

        $this->__fields->addObject($field);

        // Set parent element hard, overwrite if previously set.
        $field->setMeta("parent", $this, true);

        if ($field->isDynamic() && get_class($field) !== "ValidFormBuilder\\MultiField"
            && get_class($field) !== "ValidFormBuilder\\Area"
        ) {
            $objHidden = new Hidden($field->getId() . "_dynamic", ValidForm::VFORM_INTEGER, array(
                "default" => 0,
                "dynamicCounter" => true
            ));

            $this->__fields->addObject($objHidden);

            $field->setDynamicCounter($objHidden);
        }
    }

    /**
     * Generate HTML output for this fieldset and all it's children
     *
     * @internal
     * @param boolean $submitted Define if the area has been submitted and propagate that flag to the child fields
     * @param boolean $blnSimpleLayout Only render in simple layout mode
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors Display generated errors
     * @return string Rendered Fiedlset and child elements
     */
    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        // Call this right before __getMetaString();
        $this->setConditionalMeta();

        //*** Set the "id" if not yet set.
        $this->__setMeta("id", $this->getName(), false);

        $strOutput = "<fieldset{$this->__getMetaString()}>\n";
        if (! empty($this->__header)) {
            $strOutput .= "<legend><span>{$this->__header}</span></legend>\n";
        }

        if (is_object($this->__note)) {
            $strOutput .= $this->__note->toHtml();
        }

        foreach ($this->__fields as $field) {
            $strOutput .= $field->toHtml($submitted, $blnSimpleLayout, $blnLabel, $blnDisplayErrors);
        }

        $strOutput .= "</fieldset>\n";

        return $strOutput;
    }

    /**
     * Generate Javascript code.
     *
     * See {@link \ValidFormBuilder\Base::toJs()}
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

        // *** Render Conditions logic.
        $strReturn .= $this->conditionsToJs($intDynamicPosition);

        return $strReturn;
    }

    /**
     * Validate fieldset and it's contents
     *
     * @internal
     * @return boolean True if valid, false if not
     */
    public function isValid()
    {
        return $this->__validate();
    }

    /**
     * Returns if this element contains fields
     *
     * @internal
     * @return boolean
     */
    public function hasFields()
    {
        return true;
    }

    /**
     * Get the fields collection
     *
     * @internal
     * @return \ValidFormBuilder\Collection
     */
    public function getFields()
    {
        return $this->__fields;
    }

    /**
     * Check if this element is dynamic or not
     *
     * @internal
     * @return boolean
     */
    public function isDynamic()
    {
        return false;
    }

    /**
     * Internal validation method
     *
     * @internal
     * @return boolean
     */
    private function __validate()
    {
        $blnReturn = true;

        foreach ($this->__fields as $field) {
            if (! $field->isValid()) {
                $blnReturn = false;
                break;
            }
        }

        return $blnReturn;
    }
}
