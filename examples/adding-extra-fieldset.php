<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Adding an extra fieldset
$objFieldset = $objForm->addFieldset("Test Label", "Test header", "Test Body");
// /* @var $objCat ValidFormBuilder\Group */
$objCat = $objFieldset->addField(
    "cat",
    "Category",
    ValidForm::VFORM_RADIO_LIST,
    [],
    [],
    ["fieldclass" => "awesome-list"]
);
$objCat->addField("Red", 'R');
$objCat->addField("Green", 'G');
$objCat->addField("Blue", 'B');
$objCat->addField("Orange", 'O');
$objCat->addField("White", 'W');

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>