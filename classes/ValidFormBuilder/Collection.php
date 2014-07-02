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
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @copyright 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 */

namespace ValidFormBuilder;

/**
 * Create an iterable collection
 *
 * @package ValidForm
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 */
class Collection implements \Iterator
{

    /**
     * The array that contains all items
     * @internal
     * @var array
     */
    protected $collection = array();

    /**
     * Seek flag
     * @internal
     * @var boolean
     */
    private $isSeek = false;

    /**
     * Constructor method
     *
     * @param array $initArray An array of items to initialize the collection with.
     */
    public function __construct($initArray = array())
    {
        if (is_array($initArray)) {
            $this->collection = $initArray;
        }
    }

    /**
     * Add object to the collection
     *
     * @param object $value The object
     * @param boolean $blnAddToBeginning Add object to beginning of array or not
     */
    public function addObject($value, $blnAddToBeginning = false)
    {
        if ($blnAddToBeginning) {
            array_unshift($this->collection, $value);
        } else {
            array_push($this->collection, $value);
        }
    }

    /**
     * Add object to the collection at a specified position
     *
     * @param object $value The object
     * @param integer $intPosition The position the object should be placed at.
     */
    public function addObjectAtPosition($value, $intPosition)
    {
        $arrTempCollection = array();
        $intCount = 0;

        if ($intPosition >= $this->count()) {
            // *** Position is greater than the collection count. Just add at the end.
            $this->addObject($value);
        } else {
            foreach ($this->collection as $varObject) {
                if ($intCount == $intPosition) {
                    // *** Insert the new object.
                    array_push($arrTempCollection, $value);
                }

                // *** Insert the existing object.
                array_push($arrTempCollection, $varObject);
                $intCount ++;
            }

            // *** Replace the collection.
            $this->collection = $arrTempCollection;
        }
    }

    /**
     * Add objects to the collection
     *
     * @param array $arrObjects An array of items / collection of objects to be added
     * @param boolean $blnAddToBeginning Add objects to beginning of array or not
     */
    public function addObjects($arrObjects, $blnAddToBeginning = false)
    {
        foreach ($arrObjects as $varObject) {
            $this->addObject($varObject, $blnAddToBeginning);
        }
    }

    /**
     * Advance internal pointer to a specific index
     *
     * @param integer $intPosition The index to advance to
     */
    public function seek($intPosition)
    {
        if (is_numeric($intPosition) && $intPosition < count($this->collection)) {
            reset($this->collection);
            while ($intPosition < key($this->collection)) {
                next($this->collection);
            }
        }

        $this->isSeek = true;
    }

    /**
     * Pick a random child element
     * @return mixed Randomly selected item from collection
     */
    public function random()
    {
        $objReturn = null;

        $intIndex = rand(0, (count($this->collection) - 1));
        if (isset($this->collection[$intIndex])) {
            $objReturn = $this->collection[$intIndex];
        }

        return $objReturn;
    }

    /**
     * Randomize the collection
     */
    public function randomize()
    {
        shuffle($this->collection);
    }

    /**
     * Get the item count.
     * @return integer The length of the collection
     */
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Get the current item from the collection.
     * @return mixed The current item
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Place the pointer one item forward and return the item.
     * @return mixed The next item
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * Place the pointer one item back and return the item.
     * @return mixed The previous item
     */
    public function previous()
    {
        return prev($this->collection);
    }

    /**
     * Get the current position of the pointer.
     * @return mixed|null The key function simply returns the
     * key of the array element that's currently being pointed to by the internal pointer. It does not move the
     * pointer in any way. If the internal pointer points beyond the end of the elements list or the array is empty,
     * key returns `null`.
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Check if the pointer is at the first record.
     * @return boolean True if current item is first item
     */
    public function isFirst()
    {
        return key($this->collection) == 0;
    }

    /**
     * Check if the pointer is at the last record.
     * @return boolean True if current item is last item
     */
    public function isLast()
    {
        return key($this->collection) == (count($this->collection) - 1);
    }

    /**
     * Get first element in collection
     * @return mixed Returns first element in collection, null if collection is empty
     */
    public function getFirst()
    {
        $varReturn = null;
        if (count($this->collection) > 0) {
            $varReturn = $this->collection[0];
        }

        return $varReturn;
    }

    /**
     * Get last element in collection
     *
     * @param string $strType Optional type to search for. For example, you can search the Collection for the
     * last Hidden element by doing: `$objCollection->getLast("\\ValidFormBuilder\\Hidden")`.
     * @return mixed Returns last element in collection, null if collection is empty
     */
    public function getLast($strType = "")
    {
        $varReturn = null;

        if (count($this->collection) > 0) {
            if (! empty($strType)) {
                $arrTemp = array_reverse($this->collection);
                foreach ($arrTemp as $object) {
                    if (get_class($object) == $strType) {
                        $varReturn = $object;
                        break;
                    }
                }
            } else {
                $varReturn = $this->collection[$this->count() - 1];
            }
        }

        return $varReturn;
    }

    /**
     * Merge a collection with this collection.
     */
    public function merge($collection)
    {
        if (is_object($collection) && $collection->count() > 0) {
            $this->collection = array_merge($this->collection, $collection->collection);
        }
    }

    /**
     * Test if the requested item is valid.
     * @return boolean True if its valid, false if not.
     */
    public function valid()
    {
        return $this->current() !== false;
    }

    /**
     * Reset the internal pointer of the collection to the first item.
     * @return Collection Returns itself after the internal pointer is reset.
     */
    public function rewind()
    {
        if (! $this->isSeek) {
            reset($this->collection);
        }

        return $this;
    }

    /**
     * Reverse the order of the collection and return it.
     * @return Collection Returns itself after the internal collection is reversed.
     */
    public function reverse()
    {
        $this->collection = array_reverse($this->collection);
        return $this;
    }

    /**
     * Set the internal pointer of the collection to the last item and return it.
     * @return mixed The last item in the collection
     */
    public function end()
    {
        return end($this->collection);
    }

    /**
     * Rebuild the collection index.
     */
    public function rebuild()
    {
        $this->collection = array_values($this->collection);
    }

    /**
     * Check if an object is in the collection
     *
     * @param mixed $varValue Value to search for
     * @param boolean $blnReturnKey If true, returns the position of the item if found. Default: false.
     */
    public function inCollection($varValue, $blnReturnKey = false)
    {
        $varReturn = false;
        foreach ($this->collection as $object) {
            if ($object == $varValue || $varValue === get_class($object)) {
                $varReturn = ($blnReturnKey) ? $this->key() : true;
                break;
            }
        }

        // *** Reset the internal pointer.
        self::rewind();

        return $varReturn;
    }

    /**
     * Remove an element from the collection
     *
     * @param object $objElement The element that will be removed
     * @return boolean Always returns true. If it couldn't be found, its gone. If the element is found, it will
     * be removed.
     */
    public function remove($objElement)
    {
        $varKey = $this->inCollection($objElement, true);

        if ($varKey !== false) {
            // Element found. Now remove it.
            unset($this->collection[$varKey]);
        }

        $this->rebuild(); // Rebuild collection index.

        return true;
    }

    /**
     * Same as `remove()` but this is executed recursively.
     *
     * @see \ValidFormBuilder\Collection::remove()
     * @param unknown $objElement
     */
    public function removeRecursive($objElement)
    {
        foreach ($this->collection as $intKey => $objValue) {
            if ($objValue->hasFields()) {
                $objValue->getFields()->removeRecursive($objElement);
            } else {
                if ($objValue->getName() == $objElement->getName()) {
                    unset($this->collection[$intKey]);
                }
            }
        }
    }
}
