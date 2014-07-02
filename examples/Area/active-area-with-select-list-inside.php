<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Active Area with select list inside
/* @var $objCat \ValidFormBuilder\Area */
$objArea = $objForm->addArea("Cool area title.", true, "area-name", false);
$objAreaSelect = $objArea->addField("field-name", "Field label", ValidForm::VFORM_SELECT_LIST,
    array(
        "required" => false
    ),
    array(
        "required" => "This is a required field."
    )
);
$objAreaSelect->addField('value1','id1');
$objAreaSelect->addField('value2','id2');

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
