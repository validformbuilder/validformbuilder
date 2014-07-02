<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Condition and Comparison example 2 - not empty
$objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
$objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
$objFirstName->addCondition(
    'enabled', // First Name will be
    false, // 'disabled' (enabled -> false)
    array(
        // When field $objLastName 'is not empty'
        // (note that we cal leave out the third 'value' parameter in this case)
        new \ValidFormBuilder\Comparison($objLastName, ValidForm::VFORM_COMPARISON_NOT_EMPTY)
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
