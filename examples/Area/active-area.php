<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Create an active area
$objArea = $objForm->addArea("Disable fields", true, "fields-disabled");
$objArea->addField(
    "first-name",
    "First name",
    ValidForm::VFORM_STRING,
    array(
        // Make this field required
        "required" => true
    ),
    array(
        // Show this error to indicate this is an required field if no value is submitted
        "required" => "This field is required"
    )
);
$objArea->addField(
    "last-name",
    "Last name",
    ValidForm::VFORM_STRING,
    array(
        // Make this field required
        "required" => true
    ),
    array(
        // Show this error to indicate this is an required field if no value is submitted
        "required" => "This field is required"
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
