<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Multifield with tip text on first field
/* @var $objMulti ValidFormBuilder\MultiField */
$objMulti = $objForm->addMultiField("Cool label");
$objMulti->addField("Test", ValidForm::VFORM_STRING, [], [], ["tip" => "Cool stuff"]);
$objMulti->addField("Test", ValidForm::VFORM_STRING);
$objMulti->addField("Test", ValidForm::VFORM_STRING);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
