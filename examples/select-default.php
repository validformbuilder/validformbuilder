<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Standard select element
$objSelect = $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST);
$objSelect->addField("Awesome", 1);
$objSelect->addField("Great", 2);
$objSelect->addField("Super Cool", 3, true); // This item is selected by default
$objSelect->addField("Splendid", 4);
$objSelect->addField("Best thing ever happened", 5);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>