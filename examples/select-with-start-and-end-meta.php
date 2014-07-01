<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Select element with start - end meta
$objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST, array(), array(), array(
	"start" => 1,
	"end" => 5
));

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