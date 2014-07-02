<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Active Area with select list inside
/* @var $objCat \ValidFormBuilder\Area */
$objArea = $objForm->addArea("Realitzarà...migdia.", true, "activitats-migdia", false);
$objAreaSelect = $objArea->addField("activitat-migdia-dl", "Dilluns", ValidForm::VFORM_SELECT_LIST,
    array(
        "required" => false
    ),
    array(
    )
);
$objAreaSelect->addField('value1','id1');
$objAreaSelect->addField('value2','id2');

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
