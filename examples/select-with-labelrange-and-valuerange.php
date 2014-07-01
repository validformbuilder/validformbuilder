<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Select element with label and value ranges
$objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST, array(), array(), array(
	"labelRange" => array("Awesome","Great","Super Cool","Splendid","Best thing ever happened"),
	"valueRange" => array(1,2,3,4,5)
));

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>