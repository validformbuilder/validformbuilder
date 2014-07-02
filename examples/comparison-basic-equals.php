<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Basic Condition and Comparison
$objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
$objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
$objLastName->addCondition(
    'visible', // Last name will become
    false, // 'not visible' (visible -> false)
    array(
        // When field $objFirstName 'is equal to' Robin
        new \ValidFormBuilder\Comparison($objFirstName, ValidForm::VFORM_COMPARISON_EQUAL, 'Robin')
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>