<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Adding optgroups to a select element
$objSelect = $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST);
$objSelect->addGroup("Preferred rating");
$objSelect->addField("Awesome", 1);
$objSelect->addGroup("Other ratings");
$objSelect->addField("Great", 2);
$objSelect->addField("Super Cool", 3, true); // This item is selected by default
$objSelect->addField("Splendid", 4);
$objSelect->addField("Best thing ever happened", 5);

//*** Generate form output
if ($objForm->isValid() && $objForm->isSubmitted()) {
    $strCheckboxValue = $objForm->getValidField("rating")->getValue();
    $strOutput = $objForm->valuesAsHtml();
} else {
    $strOutput = $objForm->toHtml();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ValidForm Sandbox</title>
    <!-- bower:css -->
    <!-- endbower -->
	<link rel="stylesheet" type="text/css" href="vendor/composer.css">

    <!-- bower:js -->
    <script src="vendor/jquery/dist/jquery.js"></script>
    <!-- endbower -->
	<script src="vendor/composer.js"></script>
</head>
<body>

<?=$strOutput?>

</body>
</html>