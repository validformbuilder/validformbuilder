<?php

require_once("includes/init.php");
error_reporting(E_ALL);

$objForm = DBFormQuery::create()->findOneByName("contactForm");
if (!is_object($objForm) || $_GET["forceNew"] == true) {
    $objForm = new ValidWizard("contactForm", "Required fields are printed in bold.");
    
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

    $objForm->addPage("hallO", "Cool hoor!");
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

    $objForm->addPage("hallaaaa", "Meer info..");
    $objTextarea = new VF_Textarea("remarks2", VFORM_TEXT, "Anders, namelijk:", 
        array(
            "maxLength" => 2000
        ), 
        array(
            "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
            "type" => "Enter only characters, punctuation, numbers and spaces",
            "required" => "Dit veld is verplicht."
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
            )
    );
        $objCheckboxes->addField("Dit is een keuze", "i-dunno");
        $objCheckboxes->addField("Ik zou hier voor kiezen", "bored");  // HTML output: <option value="bored" selected="selected">I got bored.</option>
        $objCheckboxes->addField("Ik weet het niet zeker", "fun");
        // $objCheckboxes->addFieldObject($objTextarea);

    $objText = new VF_Text("text", VFORM_TEXT, "Anders, namelijk:", 
        array(
            "maxLength" => 2000
        ), 
        array(
            "maxLength" => "Your input is too long. A maximum of %s characters is OK.", 
            "type" => "Enter only characters, punctuation, numbers and spaces",
            "required" => "Dit veld is verplicht."
        ),
        array(
            "labelClass" => "vf__triggerfield"
        )
    );
    $objCheckboxes = $objForm->addField("source", "How did you get here?", VFORM_RADIO_LIST,
            array(
                    "required" => true
            ),
            array(
                    "required" => "No value selected."
            )
    );
        $objCheckboxes->addField("Dit is een keuze", "i-dunno");
        $objCheckboxes->addField("Ik zou hier voor kiezen", "bored");  // HTML output: <option value="bored" selected="selected">I got bored.</option>
        $objCheckboxes->addField("Ik weet het niet zeker", "fun");
        // $objCheckboxes->addFieldObject($objText);
        $objCheckboxes->addField("Ik weet het niet..", "somehow");
        $objCheckboxes->addField("Ik weet het..", "someho");

    $objForm->addField("file", "Wanneer ben je jarig?", VFORM_BOOLEAN,
            array(
                    "required" => true
            ),
            array(
                    "required" => "No value selected."
            )
    );

    // Add overview page
    $objForm->addPage("overviewaasd", "Controleren & Bevestigen");
    $objMulti = new VF_Password("hallo-secret", VFORM_PASSWORD, "Anders, namelijk:",
        array(
            "labelClass" => "awesome-multi-field"
        )
    );

    $objCheckboxes = $objForm->addField("fiasdasdle", "Wanneer ben je jarig?", VFORM_CHECK_LIST,
            array(
                    "required" => true
            ),
            array(
                    "required" => "No value selected."
            )
    );
        $objCheckboxes->addField("Vandaag", "i-dunno");
        $objCheckboxes->addField("Morgen", "bored");  // HTML output: <option value="bored" selected="selected">I got bored.</option>
        // $objCheckboxes->addFieldObject($objMulti);

    //*** As this method already states, it sets the submit button's label.
    $objForm->setSubmitLabel("Send");

    $strSerialized = serialize($objForm);
    //echo strlen($strSerialized);

    //$objNewForm = unserialize($strSerialized);

    $objDBForm = new DBForm();
    $objDBForm->setName($objForm->getName());
    $objDBForm->setSerialized($strSerialized);
    $objDBForm->save();
} else {
    // The form is loaded form the database, unserialize and load data.
    $objForm = $objForm->getUnserialized();
}

if ($objForm->isSubmitted() && $objForm->isValid()) {
    // Save the submitted form, serialized.
    $objDBForm = DBFormQuery::create()->findOneByName($objForm->getName());
    $objDBForm->setSerialized(serialize($objForm));
    $objDBForm->save();

    $strOutput = $objForm->valuesAsHtml();
    // echo "<hr />";
} else {
    $strOutput = $objForm->toHtml();
}




?>
<!DOCTYPE html>
<html>
<head>
	<title>VFB demo page</title>
	<link rel="stylesheet" type="text/css" href="/css/validform.css" />
</head>
<script src="/libraries/jquery.js"></script>
<style>
/* Little joke, can be removed. */
#fontBombConfirmation {display: none !important}
</style>
<body>
	<?php
		echo $strOutput;
	?>

<script src="/libraries/hash.js"></script>
<script src="/libraries/validform.js"></script>
</body>
</html>