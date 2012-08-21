<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/
  
require_once('class.classdynamic.php');

/**
 * 
 * Page Class
 * 
 * @package ValidForm
 * @author Felix Langfeldt
 * @version Release: 0.2.2
 *
 */
class VF_Page extends ClassDynamic {
	protected $__class;
	protected $__style;
	protected $__elements;
	protected $__header;
	protected $__id;
	protected $__isOverview;
	
	public function __construct($id = "", $header = "", $meta = array()) {
		$this->__header = $header;
		$this->__class = (isset($meta["class"])) ? $meta["class"] : "";
		$this->__style = (isset($meta["style"])) ? $meta["style"] : "";
		$this->__id = (empty($id)) ? $this->getRandomId("vf__page") : $id;
		$this->__isOverview = (isset($meta["overview"])) ? $meta["overview"] : false;

		$this->__elements = new VF_Collection();
	}
	
	public function toHtml($submitted = FALSE) {
		$strClass = (!empty($this->__class)) ? " class=\"{$this->__class} vf__page\"" : "class=\"vf__page\""; 
		$strStyle = (!empty($this->__style)) ? " style=\"{$this->__style}\"" : ""; 
		$strId		= " id=\"{$this->__id}\"";
		$strOutput = "<div {$strClass}{$strStyle}{$strId}>\n";
		if (!empty($this->__header)) $strOutput .= "<h2>{$this->__header}</h2>\n";
		
		if (!$this->__isOverview) {
			foreach ($this->__elements as $field) {
				$strOutput .= $field->toHtml($submitted);
			}
		}

		$strOutput .= "</div>\n";

	
		return $strOutput;
	}

	public function addField($objField) {
		if (get_class($objField) == "VF_Fieldset") {
			$this->__elements->addObject($objField);
		} else {
			if ($this->__elements->count() == 0) {
				$objFieldset = new VF_Fieldset();
				$this->__elements->addObject($objFieldset);
			}

			$objFieldset = $this->__elements->getLast();
			$objFieldset->getFields()->addObject($objField);

			if ($objField->isDynamic() 
				&& get_class($objField) !== "VF_MultiField" 
				&& get_class($objField) !== "VF_Area") {

				$objHidden = new VF_Hidden($objField->getId() . "_dynamic", VFORM_INTEGER, array("default" => 0, "dynamicCounter" => true));
				$objFieldset->addField($objHidden);

				$objField->setDynamicCounter($objHidden);
			}
		}
	}
	
	public function toJS() {
		$strReturn = "objForm.addPage('" . $this->getId() . "', true);\n";
		
		foreach ($this->__elements as $field) {
			$strReturn .= $field->toJS();
		}
		
		return $strReturn;
	}
	
	public function isValid() {
		return $this->__validate();
	}
	
	public function hasFields() {
		return ($this->__elements->count() > 0) ? TRUE : FALSE;
	}
	
	public function getFields() {
		return $this->__elements;
	}
	
	public function getRandomId($name) {
		$strReturn = $name;
		
		if (strpos($name, "[]") !== FALSE) {
			$strReturn = str_replace("[]", "_" . rand(100000, 900000), $name);
		} else {
			$strReturn = $name . "_" . rand(100000, 900000);
		}
		
		return $strReturn;
	}
	
	private function __validate() {
		$blnReturn = TRUE;
		
		foreach ($this->__elements as $field) {
			if (!$field->isValid()) {
				$blnReturn = FALSE;
				break;
			}
		}
		
		return $blnReturn;
	}
	
}

?>