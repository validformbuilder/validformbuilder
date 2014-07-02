<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Required checklist
/* @var $objCheck ValidFormBuilder\Group */
$objCheck = $objForm->addField("cool", "Awesome field", ValidForm::VFORM_CHECK_LIST, array("required" => true));
$objCheck->addField("Cool stuff", "coolio");
$objCheck->addField("Cool stuff2", "coolio2");
$objCheck->addField("Cool stuff3", "coolio3");

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>