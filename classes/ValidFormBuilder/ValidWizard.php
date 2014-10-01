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
 * @package ValidWizard
 * @author Felix Langfeldt <felix@neverwoods.com>, Robin van Baalen <robin@neverwoods.com>
 * @copyright 2009-2014 Neverwoods Internet Technology - http://neverwoods.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://validformbuilder.org
 * @version 3.0.0
 */

namespace ValidFormBuilder;

/**
 * ValidWizard class - Create multiple pages with formfields and next - previous buttons
 *
 * **Note**: Make sure you also include `validwizard.js` when using ValidWizard. This javascript library is not
 * required when you're not using ValidWizard.
 *
 * #### Example; Create a ValidWizard instance
 * ```php
 * // The signature is exactly the same as with ValidForm
 * $objForm = new ValidWizard(
 *     "awesome-wizard",
 *     "Please fill out my cool wizard",
 *     "/stuff",
 *     array(
 *         // When no 'nextLabel' meta is set, defaults to 'Next &rarr;'
 *         "nextLabel" => "Move on &rarr;",
 *         // When no 'previousLabel' meta is set, defaults to '&larr; Previous'
 *         "previousLabel" => "Retreat!"
 *     )
 * );
 * ```
 *
 * #### Example 2; Add a page
 * ```php
 * $objForm->addPage(
 *     "personal-details",
 *     "Personal Details"
 * );
 * ```
 *
 * @package ValidWizard
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version Release: 3.0.0
 *
 * @method integer getPageCount() getPageCount() Returns the number of pages in the wizard
 * @method integer getCurrentPage() getCurrentPage() Returns the current page counter
 * @method string getPreviousLabel() getPreviousLabel() Returns the label of the previous button
 * @method void setPreviousLabel() setPreviousLabel($strLabel) Sets the label of the previous button
 * @method string getNextLabel() getNextLabel() Returns the label of the next button
 * @method void setNextLabel() setNextLabel($strLabel) Sets the label of the next button
 */
class ValidWizard extends ValidForm
{
    /**
     * The total page count
     * @internal
     * @var integer
     */
    public $__pagecount = 0;

    /**
     * The current page index
     * @internal
     * @var integer
     */
    protected $__currentpage = 1;

    /**
     * The previous button label
     * @internal
     * @var string
     */
    protected $__previouslabel;

    /**
     * The next label button
     * @internal
     * @var string
     */
    protected $__nextlabel;

    /**
     * The previous button class
     * @internal
     * @var string
     */
    protected $__previousclass;

    /**
     * The next button class
     * @internal
     * @var string
     */
    protected $__nextclass;

    /**
     * Flag if wizard has confirm page
     * @internal
     * @var boolean
     */
    protected $__hasconfirmpage = false;

    /**
     * Create an instance of the ValidForm Builder
     *
     * @param string $name The name and id of the form in the HTML DOM and JavaScript.
     * @param string $description Desriptive text which is displayed above the form. Default `null`
     * @param string|null $action Form action. If left empty the form will post to itself. Default `null`
     * @param array $meta The meta array
     */
    public function __construct($name, $description = null, $action = null, $meta = array())
    {
        parent::__construct($name, $description, $action, $meta);

        $this->__nextlabel = (isset($meta["nextLabel"])) ? $meta["nextLabel"] : "Next &rarr;";
        $this->__previouslabel = (isset($meta["previousLabel"])) ? $meta["previousLabel"] : "&larr; Previous";
        $this->__nextclass = (isset($meta["nextClass"])) ? $meta["nextClass"] : "";
        $this->__previousclass = (isset($meta["previousClass"])) ? $meta["previousClass"] : "";
    }

    /**
     * Check if the wizard is submitted
     *
     * See {@link \ValidFormBuilder\ValidForm::isSubmitted()}
     *
     * @return boolean
     */
    public function isSubmitted($blnForce = false)
    {
        $blnReturn = false;

        if (ValidForm::get("vf__dispatch") == $this->__name) {
            // *** Try to retrieve the uniqueId from a REQUEST value.
            $strUniqueId = ValidWizard::get("vf__uniqueid");
            if (! empty($strUniqueId)) {
                $this->__setUniqueId($strUniqueId);
            }

            $blnReturn = true;
        } elseif ($blnForce) {
            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Add multifield
     *
     * See {@link \ValidFormBuilder\ValidForm::addMultiField()}
     *
     * @see \ValidFormBuilder\ValidForm::addMultiField()
     */
    public function addMultiField($label = null, $meta = array())
    {
        $objField = new MultiField($label, $meta);

        $objField->setRequiredStyle($this->__requiredstyle);

        // *** Page already defined?
        $objPage = $this->__elements->getLast("ValidFormBuilder\\Page");
        if ($this->__elements->count() == 0 || ! is_object($objPage)) {
            $objPage = $this->addPage();
        }

        // *** Fieldset already defined?
        $objFieldset = $objPage->getElements()->getLast("ValidFormBuilder\\Fieldset");
        if ($this->__elements->count() == 0 || ! is_object($objFieldset)) {
            $objFieldset = $this->addFieldset();
        }

        $objField->setMeta("parent", $objFieldset, true);

        // *** Add field to the fieldset.
        $objFieldset->addField($objField);

        return $objField;
    }

    /**
     * Get a page from the collection based on it's zero-based position in the elements collection
     *
     * @param Integer $intIndex Zero-based position
     * @return \ValidFormBuilder\Page Page element, if found.
     */
    public function getPage($intIndex = 0)
    {
        $intIndex --; // Convert page no. to index no.
        $this->__elements->seek($intIndex);

        $objReturn = $this->__elements->current();
        if ($objReturn === false || get_class($objReturn) !== "ValidFormBuilder\\Page") {
            $objReturn = null;
        }

        return $objReturn;
    }

    /**
     * Add a page to the wizard
     *
     * See {@link \ValidFormBuilder\Page}
     *
     * @param string $id Page ID
     * @param string $header Page title
     * @param array $meta Meta array
     * @return \ValidFormBuilder\Page
     */
    public function addPage($id = "", $header = "", $meta = array())
    {
        $objPage = new Page($id, $header, $meta);
        $this->__elements->addObject($objPage);

        if ($this->__elements->count() == 1) {
            // Add unique id field.
            $this->addHiddenField("vf__uniqueid", ValidForm::VFORM_STRING, array(
                "default" => $this->getUniqueId()
            ));
        }

        $this->__pagecount ++;

        return $objPage;
    }

    /**
     * Set confirmpage flag to true.
     * This allows for client-side confirmation page injection. More details on this will follow.
     */
    public function addConfirmPage()
    {
        $this->__hasconfirmpage = true;
    }

    /**
     * Reset the confirm page flag back to false
     */
    public function removeConfirmPage()
    {
        $this->__hasconfirmpage = false;
    }

    /**
     * Check if this Wizard has a confirm page flag set.
     * @return boolean
     */
    public function hasConfirmPage()
    {
        return ! ! $this->__hasconfirmpage;
    }

    /**
     * Add a field
     *
     * See {@link \ValidFormBuilder\ValidForm::addField()}
     *
     * @see \ValidFormBuilder\ValidForm::addField()
     */
    public function addField($name, $label, $type, $validationRules = array(), $errorHandlers = array(), $meta = array(), $blnJustRender = false)
    {
        $objField = parent::renderField($name, $label, $type, $validationRules, $errorHandlers, $meta);

        // *** Fieldset already defined?
        if ($this->__elements->count() == 0 && ! $blnJustRender) {
            $objPage = $this->addPage();
        }

        $objField->setRequiredStyle($this->__requiredstyle);

        if (! $blnJustRender) {
            $objPage = $this->__elements->getLast();
            $objPage->addField($objField);
        }

        return $objField;
    }

    /**
     * Add a fieldset
     *
     * See {@link \ValidFormBuilder\ValidForm::addFieldset()}
     *
     * @see \ValidFormBuilder\ValidForm::addFieldset()
     */
    public function addFieldset($label = null, $noteHeader = null, $noteBody = null, $options = array())
    {
        $objFieldSet = new Fieldset($label, $noteHeader, $noteBody, $options);

        $objPage = $this->__elements->getLast("ValidFormBuilder\\Page");
        if (! is_object($objPage)) {
            $objPage = $this->addPage();
        }

        $objPage->addField($objFieldSet);

        return $objFieldSet;
    }

    /**
     * Generate valuesAsHtml overview
     *
     * See {@link \ValidFormBuilder\ValidForm::valuesAsHtml()}
     *
     * @see \ValidFormBuilder\ValidForm::valuesAsHtml()
     */
    public function valuesAsHtml($hideEmpty = false, $collection = null)
    {
        $strTable = "\t<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"validform\">\n";
        $strTableOutput = "";
        $collection = (!is_null($collection)) ? $collection : $this->__elements;

        foreach ($collection as $objPage) {
            if (get_class($objPage) === "ValidFormBuilder\\Page") {
                // Passing 'true' will return the optional 'short header' if available.
                $strHeader = $objPage->getShortHeader();

                $strTableOutput .= "<tr><td colspan=\"3\" class=\"vf__page-header\">{$strHeader}</td></tr>";
                foreach ($objPage->getFields() as $objFieldset) {
                    $strSet = "";
                    $strTableOutput .= parent::fieldsetAsHtml($objFieldset, $strSet, $hideEmpty);
                }
            }
        }

        if (! empty($strTableOutput)) {
            return $strTable . $strTableOutput . "</table>";
        } else {
            if (! empty($this->__novaluesmessage)) {
                return $strTable . "<tr><td colspan=\"3\">{$this->__novaluesmessage}</td></tr></table>";
            } else {
                return "";
            }
        }
    }

    /**
     * Unserialize a previously serialized ValidWizard object
     *
     * @param string $strSerialized Serialized ValidWizard object
     * @param string $strUniqueId Use this to overwrite the deserialized wizard's unique ID
     * @return \ValidFormBuilder\ValidForm A ValidForm instance (this can either be a ValidForm or ValidWizard object)
     */
    public static function unserialize($strSerialized, $strUniqueId = "")
    {
        $objReturn = parent::unserialize($strSerialized);

        if (get_class($objReturn) == "ValidFormBuilder\\ValidWizard" && ! empty($strUniqueId)) {
            $objReturn->__setUniqueId($strUniqueId);
        }

        return $objReturn;
    }

    /**
     * Generate Javascript code
     *
     * See {@link \ValidFormBuilder\ValidForm::toJs()}
     *
     * @internal
     * @param string $strCustomJs Optional custom javascript code to be executed at the same
     * time the form is initialized
     * @param array $arrInitArguments Only use this when initializing a custom client-side object. This is a flat array
     * of arguments being passed to the custom client-side object.
     * @param string $blnRawJs If set to true, the generated javascript will not be wrapped in a <script> element. This
     * is particulary useful when generating javascript to be returned to an AJAX response.
     * @return string Generated javascript
     */
    protected function __toJs($strCustomJs = "", $arrInitArguments = array(), $blnRawJs = false)
    {
        // Add extra arguments to javascript initialization method.
        if ($this->__currentpage > 1) {
            $arrInitArguments["initialPage"] = $this->__currentpage;
        }

        $arrInitArguments["confirmPage"] = $this->__hasconfirmpage;

        $strJs = "";
        $strJs .= "objForm.setLabel('next', '" . $this->__nextlabel . "');\n\t";
        $strJs .= "objForm.setLabel('previous', '" . $this->__previouslabel . "');\n\t";

        if (!empty($this->__nextclass)) {
        	$strJs .= "objForm.setClass('next', '" . $this->__nextclass . "');\n\t";
        }

        if (!empty($this->__previousclass)) {
        	$strJs .= "objForm.setClass('previous', '" . $this->__previousclass . "');\n\t";
        }

        if (strlen($strCustomJs) > 0) {
            $strJs .= $strCustomJs;
        }

        return parent::__toJs($strJs, $arrInitArguments, $blnRawJs);
    }

    /**
     * Add hidden fields
     *
     * @internal
     * @return string
     */
    private function __addHiddenFields()
    {
        $strOutput = "";
        foreach ($this->getElements() as $objPage) {
            if (get_class($objPage) == "ValidFormBuilder\\Hidden") {
                continue;
            }

            foreach ($objPage->getElements() as $objFieldSet) {
                foreach ($objFieldSet->getFields() as $objField) {
                    if ($objField->hasFields()) {
                        foreach ($objField->getFields() as $objSubField) {
                            if (get_class($objSubField) == "ValidFormBuilder\\Hidden") {
                                $strOutput .= $objSubField->toHtml(true);
                            }
                        }
                    } else {
                        if (get_class($objField) == "ValidFormBuilder\\Hidden") {
                            $strOutput .= $objField->toHtml(true);
                        }
                    }
                }
            }
        }

        return $strOutput;
    }

    /**
     * Validate all form fields EXCLUDING the fields in the given page object and beyond.
     *
     * This is useful when partially validating the wizard
     *
     * @param string $strPageId The page object id
     * @return boolean True if all fields validate, false if not.
     */
    public function isValidUntil($strPageId)
    {
        $blnReturn = true;

        foreach ($this->__elements as $objPage) {
            if (! $blnReturn || $objPage->getId() == $strPageId) {
                break;
            }

            if (! $objPage->isValid()) {
                $blnReturn = false;
            }
        }

        return $blnReturn;
    }

    /**
     * Validate all form fields EXCLUDING the fields in the given page object and beyond.
     * @param string $strPageId
     * @return array Array of invalid fields
     */
    public function getInvalidFieldsUntil($strPageId)
    {
        $arrReturn = array();

        foreach ($this->__elements as $objPage) {
            if ($objPage->getId() == $strPageId) {
                break;
            }

            if ($objPage->hasFields()) {
                $objFieldsets = $objPage->getFields();
                foreach ($objFieldsets as $objFieldset) {
                    foreach ($objFieldset->getFields() as $objField) {
                        if (is_object($objField)) {
                            if ($objField->hasFields()) {
                                foreach ($objField->getFields() as $objSubField) {
                                    if (is_object($objSubField)) {
                                        if ($objSubField->hasFields()) {
                                            foreach ($objSubField->getFields() as $objSubSubField) {
                                                if (is_object($objSubSubField)) {
                                                    if (! $objSubSubField->isValid()) {
                                                        $arrTemp = array(
                                                            $objSubSubField->getName() => $objSubSubField->getValidator()->getError()
                                                        );
                                                        array_push($arrReturn, $arrTemp);
                                                    }
                                                }
                                            }
                                        } else {
                                            if (! $objSubField->isValid()) {
                                                $arrTemp = array(
                                                    $objSubField->getName() => $objSubField->getValidator()->getError()
                                                );
                                                array_push($arrReturn, $arrTemp);
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (! $objField->isValid()) {
                                    $arrTemp = array(
                                        $objField->getName() => $objField->getValidator()->getError()
                                    );
                                    array_push($arrReturn, $arrTemp);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $arrReturn;
    }

    /**
     * getFields creates a flat collection of all form fields.
     *
     * @internal
     * @param boolean $blnIncludeMultiFields Set this to true if you want to include MultiFields in the collection
     * @return Collection The collection of fields.
     */
    public function getFields($blnIncludeMultiFields = false)
    {
        $objFields = new Collection();

        foreach ($this->__elements as $objPage) {
            if ($objPage->hasFields()) {
                foreach ($objPage->getFields() as $objFieldset) {
                    if ($objFieldset->hasFields()) {
                        foreach ($objFieldset->getFields() as $objField) {
                            if (is_object($objField)) {
                                if ($objField->hasFields()) {
                                    // Also add the multifield to the resulting collection, if $blnIncludeMultiFields is true.
                                    if (get_class($objField) == "ValidFormBuilder\\MultiField" && $blnIncludeMultiFields) {
                                        $objFields->addObject($objField);
                                    }

                                    foreach ($objField->getFields() as $objSubField) {
                                        if (is_object($objSubField)) {
                                            if ($objSubField->hasFields()) {
                                                // Also add the multifield to the resulting collection, if $blnIncludeMultiFields is true.
                                                if (get_class($objField) == "ValidFormBuilder\\MultiField" && $blnIncludeMultiFields) {
                                                    $objFields->addObject($objField);
                                                }

                                                foreach ($objSubField->getFields() as $objSubSubField) {
                                                    if (is_object($objSubSubField)) {
                                                        $objFields->addObject($objSubSubField);
                                                    }
                                                }
                                            } else {
                                                $objFields->addObject($objSubField);
                                            }
                                        }
                                    }
                                } else {
                                    $objFields->addObject($objField);
                                }
                            }
                        }
                    } else {
                        $objFields->addObject($objFieldset);
                    }
                }
            } else {
                $objFields->addObject($objPage);
            }
        }

        return $objFields;
    }

    /**
     * See {@link \ValidFormBuilder\ValidForm::isValid()}
     * @see \ValidFormBuilder\ValidForm::isValid()
     */
    public function isValid($strPageId = null)
    {
        if (! is_null($strPageId)) {
            return $this->isValidUntil($strPageId);
        } else {
            return parent::isValid();
        }
    }
}
