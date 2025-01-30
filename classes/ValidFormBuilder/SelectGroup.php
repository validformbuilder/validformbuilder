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
 * Adds a SelectGroup (<optgroup>) to Select object
 *
 * See {@link \ValidFormBuilder\Select} for examples and usage.
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@stylr.nl>
 * @version 5.3.0
 */
class SelectGroup extends Base
{
    /**
     * The label
     * @var string
     */
    protected $__label;
    /**
     * The internal options collection
     * @var \ValidFormBuilder\Collection
     */
    protected $__options;

    /**
     * Construct new SelectGroup
     * @param string $label
     */
    public function __construct($label)
    {
        $this->__label = $label;
        $this->__options = new Collection();
    }

    /**
     * Generte HTML output
     * @param string $value
     * @return string Generated HTML
     */
    public function toHtmlInternal($value = null)
    {
        $strOutput = "<optgroup label=\"{$this->__label}\">\n";
        foreach ($this->__options as $option) {
            $strOutput .= $option->toHtmlInternal($value);
        }
        $strOutput .= "</optgroup>\n";

        return $strOutput;
    }

    /**
     * Add an `option` to the `optgroup`
     *
     * @param string $label Option's label
     * @param string $value Option's value
     * @param boolean $selected Set this option as selected by default
     * @param array $meta Optional extra meta data
     * @return \ValidFormBuilder\SelectOption
     */
    public function addField($label, $value, $selected = false, $meta = array())
    {
        $objOption = new SelectOption($label, $value, $selected, $meta);
        $objOption->setMeta("parent", $this, true);

        $this->__options->addObject($objOption);

        return $objOption;
    }
}
