<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Basic string input field
$objForm->addField(
    "name",
    "Enter your name",
    ValidForm::VFORM_STRING,
    [
        "externalValidation" => [
            "php" => [[ExternalValidation::class, 'validate'], ['arg1', 'arg2']],
            "javascript" => ['ExternalValidation.validate', ['arg1', 'arg2']]
        ]
    ],
    [
        "externalValidation" => "Not a even value."
    ]
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}