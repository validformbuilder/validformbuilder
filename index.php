<?php

require_once("libraries/ValidForm/class.validform.php");
error_reporting(E_ALL);

$objForm = new ValidForm("contactForm", "Required fields are printed in bold.");
//*** A 'name' field, field type is string.
$test1 = $objForm->addField("name", "Your name", VFORM_STRING, 
    array(
        "maxLength" => 255, 
        "required" => TRUE
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "required" => "This field is required.", 
        "type" => "Enter only letters and spaces."
    )
);
//*** An e-mail field, field type is email.
$test2 = $objForm->addField("email", "Email address", VFORM_EMAIL, 
    array(
        "maxLength" => 255, 
        "required" => TRUE
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "required" => "This field is required.", 
        "type" => "Use the format name@domain.com"
    ),
        array(
        		"dynamic" => true,
        		"dynamicLabel" => "+ Add one"
        )
);

//*** A 'remarks' field, field type is text (HTML: textarea)
$test3 = $objForm->addField("remarks", "Remarks", VFORM_TEXT, 
    array(
        "maxLength" => 2000
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "type" => "Enter only characters, punctuation, numbers and spaces"
    )
);

//*** Passfield matching
$objPass = $objForm->addField("pass1", "Password", VFORM_PASSWORD, 
    array(
        "maxLength" => 2000
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "type" => "Enter only characters, punctuation, numbers and spaces"
    )
);
$objForm->addField("pass2", "Repass", VFORM_PASSWORD, 
    array(
        "maxLength" => 2000,
        "matchWith" => $objPass
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "type" => "Enter only characters, punctuation, numbers and spaces"
    ),
    array(
    	"matchWith" => "Doesn't match."
    )
);

$objText = new VF_Text("remarks2", VFORM_TEXT, $label = "Anders, namelijk:", 
	array(
        "maxLength" => 2000
    ), 
    array(
        "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
        "type" => "Enter only characters, punctuation, numbers and spaces"
    ),
    array(
    	"labelClass" => "vf__triggerfield"
    )
);
$objCheckboxes = $objForm->addField("why-support", "Why do you want support?", VFORM_CHECK_LIST,
        array(
                "required" => true
        ),
        array(
                "required" => "Please tell us WHY!"
        ),
        array(
        		"dynamic" => true,
        		"dynamicLabel" => "+ Add one"
        )
);
    $objCheckboxes->addField("Dunno", "i-dunno");
    $objCheckboxes->addField("I got bored.", "bored", true);  // HTML output: <option value="bored" selected="selected">I got bored.</option>
    $objCheckboxes->addField("Just for fun", "fun");
    $objCheckboxes->addField("This is what I do", "just-because");
    $objCheckboxes->addFieldObject($objText);

//*** Setting the main alert.
$objForm->setMainAlert("One or more errors occurred. Check the marked fields and try again.");

//*** As this method already states, it sets the submit button's label.
$objForm->setSubmitLabel("Send");

$strOutput = $objForm->toHtml();


?>
<!DOCTYPE html>
<html>
<head>
	<title>VFB demo page</title>
	<link rel="stylesheet" type="text/css" href="/css/validform.css" />
</head>
<script src="/libraries/jquery.js"></script>
<body>
	<?php
		echo $strOutput;
	?>

<script src="/libraries/validform.js"></script>
</body>
</html>