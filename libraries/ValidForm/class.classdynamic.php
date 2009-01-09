<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/

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