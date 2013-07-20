# Downloading and Installing ValidForm Builder
First of all, we need to download the ValidForm Builder package. If you're using a version control system, we recommend you get the latest and greatest version from our GitHub master branch.
We've also prepared some unpack-and-go downloads in the downloads section.

## Requirements
* PHP 5.4 or later
* Latest generation web browser

Internet Explorer >= 8, Firefox >= 4, Opera >= 9, Safari >= 4 are officially supported. Any other browser (and/or version) might work as well but we can't test them all.

## Installing ValidForm Builder in your project
1. Download the latest version of the ValidForm Builder package
2. Extract the archive to the root folder of your website. 

    The package should consist of the following files and folders:
    
    * **css [folder]**
    
        *Contains the default CSS file for the form design*
        
    * **libraries [folder]** 
    
        *Holds the library files*
        
        * **ValidForm [folder]** 
    
            *The PHP files for the library*
        
        * **jquery.js [file]** 
    
            *Latest version of jQuery*
        
        * **validform.js [file]** 
    
            *Javascript part of the library*
        
    * **vf_captcha.php [file]** 
    
        *PHP file for the CAPTCHA type field.**
        
\* *At this point, Captcha fields are not fully supported and tested since the incorporated Captcha PHP library is outdated. We're working on that!*

<a name="minimal-setup"></a>
### Minimal setup 
#### PHP
For the implementation in PHP the only thing you need to do is include the `class.validform.php` file using the either the [include()](http://php.net/include), [include_once()](http://php.net/include_once), [require()](http://php.net/require) or [require_once()](http://php.net/require_once) function.

    require_once("libraries/ValidForm/class.validform.php");
    
#### HTML
The (output) HTML of the page you're using ValidForm Builder in, needs to have three files included:

* `validform.css`
* `validform.js`
* The latest version of jQuery (tested up until jQuery 1.10.0)

After that, you are ready to build your forms. If you want to start easy, check out the [simple contact form tutorial](http://www.validformbuilder.org/tutorials.html). If you want go hardcore, you can dive right in to the (inline) documented code and check out the API of ValidForm Builder

Have fun building forms!

# Getting started
An important thing to know about ValidForm Builder is that we build all forms entirely server-side. ValidForm Builder is a PHP library which generates web forms with both great client- and server-side validation support.

## Create a form
First of all, we need to create a form object like so:

    $objForm = new ValidForm("formName", "Please fill out all required fields");

The first parameter we pass to the constructor will be the form's ID in the generated HTML code.

The second parameter is the form's optional description. This description will be shown as a paragraph above the form fields.

## Add a field
This is where the fun starts. Once we've got our form object in place, we can start adding all the fancy features ValidForm Builder has to offer. Let's start with the most used method of all; `addField()`.

    $objForm->addField(string $name, string $label, integer $type [, array $validationRules [, array $errorHandlers [, array $meta [, bool $justRender = false ]]]]);
    
*Interface of the addField() method*
    
### Let's break it down
* **name**
    This will be the name and ID of the form field as a string

* **label** 
    The label of the form field as a string

* **type** 
    The field type; define a field type with one of the predefined constants as explained in the [field types section](#field-types) of this documentation.

* **validation rules** (optional) 
    An array of custom validation rules

* **validation rules** (optional) 
    An array of error handlers, corresponding with the validation rules array

* **meta** (optional) 
    Custom field meta

* **just render** (optional) 
    Legacy boolean to indicate whether or not to add the field to the internal collection (and thus parse it to the screen) or just render a field object for custom use. It's recommended to use the `ValidForm::renderField()` method instead.

#### Example
Now we can add a new 'name' (string) field to our form like this:

    $objForm->addField("firstName", "First name", VFORM_STRING);
    
*A basic example of `addField()`*

<a name="field-types"></a>
### Field types
The first part of ValidForm Builder you need to get familiar with is the different field types you can use. There is a specific field type for just about all your form needs. And the best part is, when we didn't predefine your fieldtype, you can easily [add one of your own](#custom-field-type).

We currently have 21 predefined field types in ValidForm Builder.

#### Basic field types
* `VFORM_STRING`
    *HTML element*: `<input type='text' />`
    *Validation*: Basic string validation, no special characters allowed.

* `VFORM_TEXT`
    *HTML element*: `<textarea />`
    *Validation*: Basic string validation, no special characters allowed.

* `VFORM_NUMERIC`
    *Generated HTML*: `<input type='text' />`
    *Validation*: Basic numeric validation, dots and commas are allowed.

* `VFORM_INTEGER`
    *Generated HTML*: `<input type='text' />`
    *Validation*: Strict numeric validation, no dots and commas allowed.

* `VFORM_WORD`
    *Generated HTML*: `<input type='text' />`
    *Validation*: Strict string validation, only alphanumeric characters allowed.

* `VFORM_EMAIL`
    *Generated HTML*: `<input type='text' />`
    *Validation*: Regular email address validation.

* `VFORM_PASSWORD`
    *Generated HTML*: `<input type='password' />`
    *Validation*: Basic string validation, only the following special characters are allowed: `.'"_!@#()$%^&*?`

* `VFORM_BOOLEAN`
    *Generated HTML*: `<input type='text' />`
    *Validation*: Regular expression that validates human understandable boolean input (on/off).

* `VFORM_HTML`
* `VFORM_HIDDEN`

#### Field types with predefined validation
* `VFORM_CURRENCY`
* `VFORM_DATE`
* `VFORM_SIMPLEURL`
* `VFORM_URL`
* `VFORM_FILE`

##### Example - Creating a field with a predefined validation field type.

    $objForm->addField(
        "birthday", 
        "Birthday", 
        VFORM_DATE, 
        array(
            "required" => true
        ), 
        array(
            "required" => "Please select your birthday."
        )
    );


#### List-style field types
* `VFORM_RADIO_LIST`
    This generates a list of radio input fields    

* `VFORM_CHECK_LIST`
    This generates a list of checkbox input fields

* `VFORM_SELECT_LIST`
    This generates a select list with options

##### Example - Creating a list-style field.

    $objList = $objForm->addField(
        "rating", 
        "Please rate this documentation", 
        VFORM_RADIO_LIST
    );
    $objList->addField("Awesome", "awesome");
    $objList->addField("Great", "great");
    $objList->addField("Good", "good");
    
##### Example 2 - Creating a select list with options and option groups

    $objList = $objForm->addField(
        "rating", 
        "Please rate this documentation", 
        VFORM_SELECT_LIST
    );
    
    // Adding an option group with the addGroup method
    $objGroup = $objList->addGroup("Good ratings");
        // Add sub items to the group object with addField
        $objList->addField("Awesome", "awesome");
        $objList->addField("Great", "great");
        $objList->addField("Good", "good");
    $objGroup = $objList->addGroup("Average ratings");
        $objList->addField("Average", "average");
        $objList->addField("Ok", "ok");
        $objList->addField("Not bad", "not-bad");

<a name="custom-field-type"></a>
#### Custom field types
* `VFORM_CUSTOM`
    This generates a text input field with a custom validation regular expression 

* `VFORM_CUSTOM_TEXT`
    This generates a textarea input field with a custom validation regular expression

##### Example - Validating a social security number
    $objSocialSecurity = $objForm->addField(
        "socialsecurity", 
        "Your social security number",
        VFORM_CUSTOM,
        array( 
            "validation" => "/^\d{3}-\d{2}-\d{4}$/"
        ),
        array(
            "type" => "Invalid Social Security number"
        )
    );

#### Special field types
* `VFORM_CAPTCHA`
    
At this point, the Captcha type field is not fully supported in the 2.0 version of ValidForm Builder.

### Validation Rules

The following properties are all valid keys in the validation rules array when creating a new field.

*   `minlength`
    The `minlength` property defines the minimum character length of the given input.
    Default value: not set
    Value type: integer


*   `maxlength`
    See `minlength`
    Default value: not set
    Value type: integer

*   `matchwith`
    The `matchwith` property is used to match the value of two fields. This is comes in handy when a user should re-enter their password when creating an account and you want to validate they are both the same.
    Default value: not set
    Value type: VF_Element object (any ValidForm Builder field)

*   `required`
    This one doesn't need much explanation.
    Default value: false
    Value type: boolean

*   `validation`
    As shown before, the `validation` property is used to set a custom regular expression for validation of VFORM_CUSTOM and VFORM_CUSTOM_TEXT fields.
    Default value: empty
    Value type: string (regular expression)


### Error Handlers
For each error that occures on a field, you can set a custom error message. Use one or more of the following keys in the `errorHandlers`-array when creating a field. Most error handling messages have a default (English) value.

*   `minlength`
    When a `minlength` validation rule is defined, you can also define a `minlength` error message to display when the minimum length of the field value isn't reached yet. You can insert the actual minimum length value in your error message by using `%s` (we use sprintf to insert the value).
    Default value: `The input is too short. The minimum is %s characters.`

*   `maxlength`
    When a `maxlength` validation rule is defined, you can also define a `maxlength` error message to display when the maximum length of the field value is exceeded. You can insert the actual maximum length value in your error message by using `%s` (we use sprintf to insert the value).
    Default value: `The input is too long. The maximum is %s characters.`

*   `matchwith`
    When using a `matchwith` validation rule, be sure to define the `matchwith` error message as well. When for example the two password fields don't match, this error message is shown.
    Default value: `The values do not match.`

*   `required`
    This one doesn't need much explanation either.
    Default value (when validation rule's value is `true`): `This field is required.`

*   `type`
    This error is triggered when the field value can't validate against the (pre)defined regular expression set for that field. This can for example be either a predefined VFORM_DATE field or a custom VFORM_CUSTOM field.
    Default value: `empty`

*   `hint`
    It's not allowed to post a hint value as an input value. Therefore, we trigger an error when that happens. This error message will be shown.
    Default value: `The value is the hint value. Enter your own value.`
    

### Meta

### Just Render

## Cient side API
### AJAX form handling

### Custom events

## Field meta
### Magic meta

### Reserved meta

## Conditions
### Comparisons

## Dynamic fields

## Server side API
### Custom error injection

# ValidWizard
## Cient side API
### Custom events