<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Add a multifield
$objMulti = $objForm->addMultifield("Full name");
// Note: when using addField on a multifield, we don't set a label!
$objMulti->addField(
    "first-name",
    ValidForm::VFORM_STRING,
    array(),
    array(),
    // Keep it short, this is just a first name field
    array("style" => "width: 50px")
);
$objMulti->addField("last-name", ValidForm::VFORM_STRING);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
