<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 *
 * Felix Langfeldt <felix@neverwoods.com>
 * Robin van Baalen <robin@neverwoods.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package    ValidForm
 * @author     Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright  2009-2013 Neverwoods Internet Technology - http://neverwoods.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://validformbuilder.org
 ***************************/

/**
 *
 * VF_ClassDynamic Class
 *
 * @package ValidForm
 * @author Felix Langfeldt, Robin van Baalen
 * @version Release: 0.3
 *
 * CHANGELOG
 *
 * 	- Renamed ClassDynamic to VF_ClassDynamic
 * 	- Removed all 'echo'-s and replaced them with throw new BadMethodCallException
 *
 */
class VF_ClassDynamic {

	public function __get($property) {
		$property = strtolower("__" . $property);

		if (property_exists($this, $property)) {
			return $this->$property;
		} else {
			throw new BadMethodCallException("Property Error in " . get_class($this) . "::get({$property}) on line " . __LINE__ . ".");
		}
	}

	public function __set($property, $value) {
		$property = strtolower("__" . $property);

		if (property_exists($this, $property)) {
			$this->$property = $value;
		} else {
			throw new BadMethodCallException("Property Error in " . get_class($this) . "::set({$property}, {$value}) on line " . __LINE__ . ".");
		}
	}

	public function __call($method, $values) {
		if (substr($method, 0, 3) == "get") {
			$property = substr($method, 3);
			return $this->$property;
		}

		if (substr($method, 0, 3) == "set") {
			$property = substr($method, 3);
			$this->$property = $values[0];
			return;
		}

		throw new BadMethodCallException("Method Error in " . get_class($this) . "::{$method} on line " . __LINE__ . ".");
	}

}

?>