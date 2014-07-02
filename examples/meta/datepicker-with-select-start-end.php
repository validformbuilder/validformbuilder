<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Date picker using select elements in a multifield
$objMulti = $objForm->addMultiField("Birthdate");
$objMulti->addField(
    "year",
    ValidForm::VFORM_SELECT_LIST,
    array(),
    array(),
    array(
        "start" => 1920,
        "end" => 2014,
        // 'fieldstyle' gets applied on the <select>
        // regular 'style' applies on the wrapping <div>
        "fieldstyle" => "width: 75px"
    )
);
$objMulti->addField(
    "month",
    ValidForm::VFORM_SELECT_LIST,
    array(),
    array(),
    array(
        "start" => 01,
        "end" => 12,
        "fieldstyle" => "width: 75px"
    )
);
$objMulti->addField(
    "day",
    ValidForm::VFORM_SELECT_LIST,
    array(),
    array(),
    array(
        "start" => 1,
        "end" => 31,
        "fieldstyle" => "width: 75px"
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
