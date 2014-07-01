<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Select list with tip text
$objCat = $objForm->addField("cat", "Category", ValidForm::VFORM_SELECT_LIST, [], [], ["tip" => "Cool tip"]);
$objCat->addField("Red",'R');
$objCat->addField("Green",'G');
$objCat->addField("Blue",'B');

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>