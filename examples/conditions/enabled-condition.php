<?php
/**
 * Make field enabled when other field's value is 'yes'
 * Field will becom disabled when other field's value is 'no'
 * 
 * Note: enabled/disabled is NOT the same as visible/hidden!
 */
use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Basic yes/no condition
$objCheck = $objForm->addField("yesno", "Yes or No", ValidForm::VFORM_RADIO_LIST);
$objYes = $objCheck->addField("Yes", "yes");
$objCheck->addField("No", "no");

$objText = $objForm->addField(
    "textfield",
    "Text here",
    ValidForm::VFORM_TEXT,
    array("required" => "true"),
    array("required" => "This field is required"),
    array("fielddisabled" => "disabled")
);
$objText->addCondition("enabled", true, array(
    new Comparison($objYes, ValidForm::VFORM_COMPARISON_EQUAL, "yes")
));

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
