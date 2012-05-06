<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.opensource.org/licenses/mit-license.php
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/

/**
 * 
 * ClassDynamic Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.1
 *
 */
class ClassDynamic {

	public function __get($property) {
		$property = strtolower("__" . $property);

		if (property_exists($this, $property)) {
			return $this->$property;
		} else {
			echo "Property Error in " . get_class($this) . "::get({$property}) on line " . __LINE__ . ".";
		}
	}

	public function __set($property, $value) {
		$property = strtolower("__" . $property);
		
		if (property_exists($this, $property)) {
			$this->$property = $value;
		} else {
			echo "Property Error in " . get_class($this) . "::set({$property}) on line " . __LINE__ . ".";
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

		echo "Method Error in " . get_class($this) . "::{$method} on line " . __LINE__ . ".";
	}

}

?>