<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Add a textarea
$objForm->addField(
    "message",
    "Your Message",
    ValidForm::VFORM_TEXT,
    array(
        // Make this field required
        "required" => true
    ),
    array(
        // Error message when required state isn't met
        "required" => "This is a required field"
    ),
    array(
        "cols" => 20,
        "rows" => 10
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