<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Basic string input field
$objForm->addField(
    "name",
    "Enter your password",
    ValidForm::VFORM_PASSWORD,
    [
        "required" => true,
        "validation" => '/^[0-9]*$/i',
        "minLength" => 12
    ],
    [
        "type" => "Invalid characters entered. Stick to numbers only, please.",
        "required" => "Please no empty promises",
        "minLength" => "%s characters is the least you can do"
    ]
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}
