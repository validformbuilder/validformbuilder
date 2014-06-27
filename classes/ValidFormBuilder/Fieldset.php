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

    public function addField($field)
    {
        $this->__fields->addObject($field);

        // Set parent element hard, overwrite if previously set.
        $field->setMeta("parent", $this, true);

        if ($field->isDynamic() && get_class($field) !== "ValidFormBuilder\\MultiField"
                && get_class($field) !== "ValidFormBuilder\\Area") {
            $objHidden = new Hidden($field->getId() . "_dynamic", ValidForm::VFORM_INTEGER, array(
                "default" => 0,
                "dynamicCounter" => true
            ));

            $this->__fields->addObject($objHidden);

            $field->setDynamicCounter($objHidden);
        }
    }

    public function toHtml($submitted = false, $blnSimpleLayout = false, $blnLabel = true, $blnDisplayErrors = true)
    {
        // Call this right before __getMetaString();
        $this->setConditionalMeta();

        $strOutput = "<fieldset{$this->__getMetaString()} id=\"{$this->getName()}\">\n";
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

    public function isValid()
    {
        return $this->__validate();
    }

    public function hasFields()
    {
        return true;
    }

    public function getFields()
    {
        return $this->__fields;
    }

    public function isDynamic()
    {
        return false;
    }

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
