<?php
/**
 * Demonstration of the meta key 'default'
 */
use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Check list with multiple default values
/* @var $objCat ValidFormBuilder\Group */
$objCat = $objForm->addField("cat", "Category", ValidForm::VFORM_CHECK_LIST, [], [], ["default" => ["R", "G"]]);
$objCat->addField("Red", 'R');
$objCat->addField("Green", 'G');
$objCat->addField("Blue", 'B');
$objCat->addField("Orange", 'O');
$objCat->addField("White", 'W');

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
