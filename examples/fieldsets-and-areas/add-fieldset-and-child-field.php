<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Add fieldset
$objFieldset = $objForm->addFieldset("Cool fieldset label");

/**
 * Now, instead of doing this:
 * $objFieldset->addField( .... )
 * You can just do this:
 */
$objForm->addField("hello", "Cool field in fieldset", ValidForm::VFORM_STRING);
/**
 * ValidForm Builder automatically searches for the last created Fieldset and appends the newly created field to it.
 * See issue #15 for more details
 */

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
