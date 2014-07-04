<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Add a custom button to the form
$objForm->addButton(
    "Button label",
    array(
        // Set for example a Twitter Bootstrap class on this button
        "fieldclass" => "btn btn-large"
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
