<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Match two password fields
$objNewPassword = $objForm->addField(
    "new-password",
    "New Password",
    ValidForm::VFORM_PASSWORD
);
$objForm->addField(
    "repeat-password",
    "Repeat Password",
    ValidForm::VFORM_PASSWORD,
    array(
        "matchWith" => $objNewPassword
    ),
    array(
        "matchWith" => "Password fields do not match"
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