<?php

use ValidFormBuilder\ValidForm;

require '../vendor/autoload.php';

$objForm = new ValidForm("test");

//*** Add fieldset
// $objForm->addFieldset("Cool fieldset label", $noteHeader = null, $noteBody = null, $meta = array());

//*** Required checklist
/* @var $objCheck ValidFormBuilder\Group */
// $objCheck = $objForm->addField("cool", "Awesome field", ValidForm::VFORM_CHECK_LIST, array("required" => true));
// $objCheck->addField("Cool stuff", "coolio");
// $objCheck->addField("Cool stuff2", "coolio2");
// $objCheck->addField("Cool stuff3", "coolio3");

//*** Multifield with tip text on first field
/* @var $objMulti ValidFormBuilder\MultiField */
// $objMulti = $objForm->addMultiField("Cool label");
// $objMulti->addField("Test", ValidForm::VFORM_STRING, [], [], ["tip" => "Cool stuff"]);
// $objMulti->addField("Test", ValidForm::VFORM_STRING);
// $objMulti->addField("Test", ValidForm::VFORM_STRING);

//*** Check list with multiple default values
/* @var $objCat ValidFormBuilder\Group */
// $objCat = $objForm->addField("cat", "Category", ValidForm::VFORM_CHECK_LIST, [], [], ["default" => ["R", "G"]]);
// $objCat->addField("Red", 'R');
// $objCat->addField("Green", 'G');
// $objCat->addField("Blue", 'B');
// $objCat->addField("Orange", 'O');
// $objCat->addField("White", 'W');

//*** Active Area with select list inside
/* @var $objCat ValidFormBuilder\Area */
// $objArea = $objForm->addArea("Realitzarà...migdia.", true, "activitats-migdia", false);
// $objAreaSelect = $objArea->addField("activitat-migdia-dl", "Dilluns", ValidForm::VFORM_SELECT_LIST,
//     array(
//         "required" => false
//     ),
//     array(
//     )
// );
// $objAreaSelect->addField('value1','id1');
// $objAreaSelect->addField('value2','id2');

//*** Basic yes/no condition
// $objCheck = $objForm->addField("yesno", "Yes or No", ValidForm::VFORM_RADIO_LIST);
// $objYes = $objCheck->addField("Yes", "yes");
// $objCheck->addField("No", "no");

// $objText = $objForm->addField(
//     "textfield",
//     "Text here",
//     ValidForm::VFORM_TEXT,
//     array("required" => "true"),
//     array("required" => "This field is required"),
//     array("fielddisabled" => "disabled")
// );
// $objText->addCondition("enabled", true, array(
// 	new Comparison($objYes, ValidForm::VFORM_COMPARISON_EQUAL, "yes")
// ));

//*** Basic string input field
// $objForm->addField("name", "Enter your name", ValidForm::VFORM_STRING, [], ["type" => "Invalid characters"]);

//*** Custom form button
// $objForm->addButton("Cool Button", ["fieldname" => "custom-button-name"]);

//*** Select list with tip text
// $objCat = $objForm->addField("cat", "Category", ValidForm::VFORM_SELECT_LIST, [], [], ["tip" => "Cool tip"]);
// $objCat->addField("Red",'R');
// $objCat->addField("Green",'G');
// $objCat->addField("Blue",'B');

//*** Radio list with custom class on container
/* @var $objCat ValidFormBuilder\Group */
// $objCat = $objForm->addField(
//     "cat",
//     "Category",
//     ValidForm::VFORM_RADIO_LIST,
//     [],
//     [],
//     ["fieldclass" => "awesome-list"]
// );
// $objCat->addField("Red",'R');
// $objCat->addField("Green",'G');
// $objCat->addField("Blue",'B');
// $objCat->addField("Orange",'O');
// $objCat->addField("White",'W');

//*** Adding an extra fieldset
// $objFieldset = $objForm->addFieldset("Test Label", "Test header", "Test Body");
// /* @var $objCat ValidFormBuilder\Group */
// $objCat = $objFieldset->addField(
//     "cat",
//     "Category",
//     ValidForm::VFORM_RADIO_LIST,
//     [],
//     [],
//     ["fieldclass" => "awesome-list"]
// );
// $objCat->addField("Red", 'R');
// $objCat->addField("Green", 'G');
// $objCat->addField("Blue", 'B');
// $objCat->addField("Orange", 'O');
// $objCat->addField("White", 'W');

//*** Add a custom button to the form
// $objForm->addButton(
//     "Button label",
//     array(
//         // Set for example a Twitter Bootstrap class on this button
//         "fieldclass" => "btn btn-large"
//     )
// );

//*** Create an active area
// $objArea = $objForm->addArea("Disable fields", true, "fields-disabled");
// $objArea->addField(
//     "first-name",
//     "First name",
//     ValidForm::VFORM_STRING,
//     array(
//         // Make this field required
//         "required" => true
//     ),
//     array(
//         // Show this error to indicate this is an required field if no value is submitted
//         "required" => "This field is required"
//     )
// );
// $objArea->addField(
//     "last-name",
//     "Last name",
//     ValidForm::VFORM_STRING,
//     array(
//         // Make this field required
//         "required" => true
//     ),
//     array(
//         // Show this error to indicate this is an required field if no value is submitted
//         "required" => "This field is required"
//     )
// );

//*** Add a multifield
// $objMulti = $objForm->addMultifield("Full name");
// // Note: when using addField on a multifield, we don't set a label!
// $objMulti->addField(
//     "first-name",
//     ValidForm::VFORM_STRING,
//     array(),
//     array(),
//     // Keep it short, this is just a first name field
//     array("style" => "width: 50px")
// );
// $objMulti->addField("last-name", ValidForm::VFORM_STRING);

//*** Date picker using select elements in a multifield
// $objMulti = $objForm->addMultiField("Birthdate");
// $objMulti->addField(
//     "year",
//     ValidForm::VFORM_SELECT_LIST,
//     array(),
//     array(),
//     array(
//         "start" => 1920,
//         "end" => 2014,
//         // 'fieldstyle' gets applied on the <select>
//         // regular 'style' applies on the wrapping <div>
//         "fieldstyle" => "width: 75px"
//     )
// );
// $objMulti->addField(
//     "month",
//     ValidForm::VFORM_SELECT_LIST,
//     array(),
//     array(),
//     array(
//         "start" => 01,
//         "end" => 12,
//         "fieldstyle" => "width: 75px"
//     )
// );
// $objMulti->addField(
//     "day",
//     ValidForm::VFORM_SELECT_LIST,
//     array(),
//     array(),
//     array(
//         "start" => 1,
//         "end" => 31,
//         "fieldstyle" => "width: 75px"
//     )
// );

//*** Captcha field
// $objForm->addField(
//     "test-captcha",
//     "Captchu?",
//     ValidForm::VFORM_CAPTCHA,
//     array(),
//     array(),
//     array("path" => "../vendor/neverwoods/validformbuilder/examples/")
// );

//*** Basic Condition and Comparison
// $objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
// $objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
// $objLastName->addCondition(
//     'visible', // Last name will become
//     false, // 'not visible' (visible -> false)
//     array(
//         // When field $objFirstName 'is equal to' Robin
//         new \ValidFormBuilder\Comparison($objFirstName, ValidForm::VFORM_COMPARISON_EQUAL, 'Robin')
//     )
// );

//*** Condition and Comparison example 2 - not empty
// $objFirstName = $objForm->addField('firstname', 'First name', ValidForm::VFORM_STRING);
// $objLastName = $objForm->addField('lastname', 'Last name', ValidForm::VFORM_STRING);
// $objFirstName->addCondition(
//     'enabled', // First Name will be
//     false, // 'disabled' (enabled -> false)
//     array(
//         // When field $objLastName 'is not empty'
//         // (note that we cal leave out the third 'value' parameter in this case)
//         new \ValidFormBuilder\Comparison($objLastName, ValidForm::VFORM_COMPARISON_NOT_EMPTY)
//     )
// );

//*** Standard select element
// $objSelect = $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST);
// $objSelect->addField("Awesome", 1);
// $objSelect->addField("Great", 2);
// $objSelect->addField("Super Cool", 3, true); // This item is selected by default
// $objSelect->addField("Splendid", 4);
// $objSelect->addField("Best thing ever happened", 5);

//*** Select element with label and value ranges
// $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST, array(), array(), array(
// 	"labelRange" => array("Awesome","Great","Super Cool","Splendid","Best thing ever happened"),
// 	"valueRange" => array(1,2,3,4,5)
// ));

//*** Select element with start - end meta
// $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST, array(), array(), array(
// 	"start" => 1,
// 	"end" => 5
// ));

//*** Adding optgroups to a select element
// $objSelect = $objForm->addField("rating", "Rate ValidForm Builder", ValidForm::VFORM_SELECT_LIST);
// $objSelect->addGroup("Preferred rating");
// $objSelect->addField("Awesome", 1);
// $objSelect->addGroup("Other ratings");
// $objSelect->addField("Great", 2);
// $objSelect->addField("Super Cool", 3, true); // This item is selected by default
// $objSelect->addField("Splendid", 4);
// $objSelect->addField("Best thing ever happened", 5);

//*** Match two password fields
// $objNewPassword = $objForm->addField(
//     "new-password",
//     "New Password",
//     ValidForm::VFORM_PASSWORD
// );
// $objForm->addField(
//     "repeat-password",
//     "Repeat Password",
//     ValidForm::VFORM_PASSWORD,
//     array(
//         "matchWith" => $objNewPassword
//     ),
//     array(
//         "matchWith" => "Password fields do not match"
//     )
// );

//*** Add a textarea
// $objForm->addField(
//     "message",
//     "Your Message",
//     ValidForm::VFORM_TEXT,
//     array(
//         // Make this field required
//         "required" => true
//     ),
//     array(
//         // Error message when required state isn't met
//         "required" => "This is a required field"
//     ),
//     array(
//         "cols" => 20,
//         "rows" => 10
//     )
// );

//*** Basic checkbox list
// $objCheckbox = $objForm->addField(
//     "rating",
//     "Rate ValidForm Builder",
//     ValidForm::VFORM_CHECK_LIST
// );
// $objCheckbox->addField("Awesome", 1);
// $objCheckbox->addField("Great", 2);
// $objCheckbox->addField("Super Cool", 3, true); // This item is selected by default
// $objCheckbox->addField("Splendid", 4);
// $objCheckbox->addField("Best thing ever happened", 5);

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