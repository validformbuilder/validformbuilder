ValidForm Builder
================

The ValidForm Builder is a PHP and JavaScript library that simplifies the often tedious creation of standards based web forms. Correct field validation of forms is an often overlooked problem and can lead to serious security issues. Building a form and adding validation to it was never that easy.

- The API generates XHTML Strict 1.0 compliant code.
- Field validation on the client side to minimize traffic overhead.
- Field validation on the server to enforce validation rules and prevent tempering with the form through SQL injection.
- Client side validation displays inline to improve user satisfaction. No more annoying popups that don't really tell you anything.
- Creation of complex form structures.
- Uses the popular jQuery Javascript library for DOM manipulation.
- Completely customizable using CSS rules.
- Automatic creation of field summaries for form mailers in HTML and plain text.

Why use ValidForm Builder?
----

- Super fast web form creation.
- Get rid of SQL injection problems.
- Create standards based CSS forms. No tables inside.
- Make form entry fun for the user. More feedback from your website.
- Write client- and server-side validation at the same time


Documentation
=============

[The documentation](https://github.com/neverwoods/validformbuilder/blob/master/docs/documentation.md) still isn't as complete as we'd like it to be but it should enable you to get started with the essential basics of ValidForm Builder.

If you have any questions, please ask them on [StackOverflow.com](http://stackoverflow.com) and be sure to tag your question with the 'validform' tag. We regulary monitor these questions and try to answer them as soon as we can :)

Quick explanation of Conditions and Comparisons in ValidForm Builder
---------

This feature is as new as it is powerful. Since *ValidForm Builder 2.0 public beta* (that's what we called it back then), one of the many new awesome features are conditional fields. Here's a quick preview on how they work:

1) Create two regular fields

```
$objFirstName = $objForm->addField("name", "Your name", VFORM_STRING, array("required" => true), array("required" => "This field is required"));
$objLastName = $objForm->addField("lastname", "Last name", VFORM_STRING, array("required" => true), array("required" => "This field is required for almost everyone..")); // not required
```

2) Now, add a condition to the `lastname` field. For example, we want it to become optional when `name` is `Robin`. After all, we all know that Robin's last name is 'Hood'. So the way we'll write that out in plain text would be: lastname-field's property `required` will become `false` when name-field's `value` will be `Robin`. Here's how the condition will look in PHP:

```
$objLastName->addCondition("required", false, array(
    new VF_Comparison($objFirstName, VFORM_COMPARISON_EQUAL, "robin") // Comparison values are case insensitive.
), VFORM_MATCH_ANY);
```

3) When you run this example and type in 'Robin' in the name field, last name will become optional. As always with ValidForm Builder: this validates both client-side and server-side!

For more information you'll just have to dig in the code for now. More than ever we added comments and PHPDoc blocks to all the code we've been working on. This release is for those who don't need to RTFM.


Happy coding!
------

Felix & Robin
