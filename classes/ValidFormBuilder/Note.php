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
 * Note Class
 *
 * @internal
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 3.0.0
 */
class Note extends Base
{
    /**
     * The note header
     * @internal
     * @var string
     */
    protected $__header;

    /**
     * The note body
     * @internal
     * @var string
     */
    protected $__body;

    /**
     * Create new Note instance
     * @internal
     * @param string $header The note's header
     * @param string $body The note's body
     * @param array $meta The meta array
     */
    public function __construct($header = null, $body = null, $meta = array())
    {
        $this->__header = $header;
        $this->__body = $body;

        $this->__meta = $meta;
        $this->__initializeMeta();
    }

    /**
     * Render the Note
     *
     * @internal
     * @param boolean $submitted Define if the element has been submitted and propagate that flag to the child fields
     * @param boolean $blnSimpleLayout Only render in simple layout mode
     * @param boolean $blnLabel
     * @param boolean $blnDisplayErrors Display generated errors
     * @return string Rendered Note
     */
    public function toHtml()
    {
        $this->setMeta("class", "vf__notes");

        $this->setConditionalMeta();
        $strOutput = "<div{$this->__getMetaString()}>\n";

        if (! empty($this->__header)) {
            $strOutput .= "<h4{$this->__getLabelMetaString()}>$this->__header</h4>\n";
        }

        if (! empty($this->__body)) {
            if (preg_match("/<p.*?>/", $this->__body) > 0) {
                $strOutput .= "{$this->__body}\n";
            } else {
                $strOutput .= "<p>{$this->__body}</p>\n";
            }
        }
        $strOutput .= "</div>\n";

        return $strOutput;
    }
}
