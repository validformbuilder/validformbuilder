<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Create a file upload field
$objForm->addField(
    "logo",
    "Upload logo",
    ValidForm::VFORM_FILE,
    array(),
    array(),
    array(
        // This results in
        // <input type="file" value="" name="logo[]" id="logo" class="vf__file validform-logo">
        "fieldclass" => "validform-logo"
    )
);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
