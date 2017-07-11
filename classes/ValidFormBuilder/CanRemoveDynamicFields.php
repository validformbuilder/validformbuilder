<?php

namespace ValidFormBuilder;

trait CanRemoveDynamicFields
{

    /**
     * The label which a user can click to remove a cloned dynamic area
     * @internal
     * @var string
     */
    protected $__dynamicRemoveLabel;

    /**
     * Get meta information for dynamic fields and set local properties
     */
    protected function initialiseDynamicRemoveMeta()
    {
        $this->__dynamicRemoveLabel = $this->getMeta("dynamicRemoveLabel", null);
    }

    /**
     * @return string
     */
    protected function getDynamicRemoveLabel()
    {
        return $this->__dynamicRemoveLabel;
    }

    /**
     * @param $label
     * @return string
     */
    protected function getRemoveLabelHtml($label = null)
    {
        $label = (is_null($label)) ? $this->getDynamicRemoveLabel() : $label;
        return "<a class='vf__removeLabel' href='#'>" . $label . "</a>";
    }

    /**
     * @return bool
     */
    protected function hasRemoveLabel()
    {
        return !is_null($this->getDynamicRemoveLabel());
    }

    /**
     * @return bool
     */
    protected function isRemovable()
    {
        return $this->hasRemoveLabel();
    }

    protected function unsetDynamicRemoveLabelMeta($meta)
    {
        if (array_key_exists('dynamicRemoveLabel', $meta)) {
            unset($meta['dynamicRemoveLabel']);
        }

        return $meta;
    }
}