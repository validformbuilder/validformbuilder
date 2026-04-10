# AGENTS.md

This file provides guidance to autonomous coding agents (Claude Code, GPT-based copilots, etc.) when working with code in this repository.

## Project Overview

ValidForm Builder is a PHP library for creating XHTML-compliant forms with client-side and server-side validation. It generates HTML forms with JavaScript validation using jQuery.

## Architecture

### Core Classes Structure
- **ValidForm**: Main form class (`classes/ValidFormBuilder/ValidForm.php`) - entry point for form creation
- **Base**: Abstract parent class for all form elements with shared functionality (meta handling, conditions, dynamic fields)
- **Element**: Base class for all form field types with validation capabilities
- **Collection**: Container class for managing groups of form elements
- **Validator**: Handles field validation rules and error messages
- **Condition/Comparison**: Implements conditional logic for showing/hiding/requiring fields

### Element Types
Form fields are created using `ValidForm::VFORM_*` constants:
- Text inputs: `VFORM_STRING`, `VFORM_EMAIL`, `VFORM_NUMERIC`, `VFORM_PASSWORD`
- Complex inputs: `VFORM_SELECT_LIST`, `VFORM_CHECK_LIST`, `VFORM_RADIO_LIST`
- Content: `VFORM_TEXT` (textarea), `VFORM_FILE`, `VFORM_PARAGRAPH`
- Containers: Fieldset, Area, MultiField

### Key Patterns
- **Meta System**: All elements support flexible metadata for styling, attributes, and behavior
- **Dynamic Fields**: Elements can be cloned dynamically on the client-side with `_1`, `_2` suffixes
- **Conditional Logic**: Fields can be shown/hidden/required based on other field values
- **Validation**: Dual client/server validation with the same rules

## Development Commands

### Composer
```bash
# Install dependencies (includes phpdocumentor shim)
composer install

# The library is published as: validformbuilder/validformbuilder
```

### Documentation Generation
```bash
# Generate API documentation using the bundled phpdoc shim
vendor/bin/phpdoc

# Configuration lives in phpdoc.xml; visibility is explicit, so
# always call the vendor binary to ensure consistent behavior.
```

### Linting / Testing
- There is no PHPUnit suite. Validate changes by running relevant examples in `examples/` or exercising affected classes directly.
- Use `php -l <file>` for fast syntax checks when editing PHP files.
- When touching JavaScript (e.g., `js/validform-webcomponents.js`), open the example pages to verify behavior manually.

## Code Conventions

### Naming
- Class properties use `$__property` prefix for internal properties
- Dynamic field names get `_N` suffix (e.g., `fieldname_1`, `fieldname_2`)
- Method names use camelCase
- Constants use `VFORM_` prefix

### Meta Arrays
- `meta` - General HTML attributes and custom data
- `fieldmeta` - Specific to the input element
- `labelmeta` - Specific to the label element
- Magic prefixes: `field`, `label`, `tip`, `dynamicLabel` automatically sort meta into correct arrays

### Form Creation Pattern
1. Create ValidForm instance: `new ValidForm($name, $description, $action)`
2. Add fields: `$form->addField($name, $label, $type, $validationRules, $errorHandlers, $meta)`
3. Handle submission: Check `$form->isSubmitted()` and `$form->isValid()`
4. Get validated values: `$form->getValidField($name)->getValue()`
5. Output: `$form->toHtml()` for complete form or `$form->toJS()` for AJAX

### Example Structure
```php
$objForm = new ValidForm('example_form', 'Form Description', '/submit');
$objForm->addField('email', 'Email Address', ValidForm::VFORM_EMAIL, 
    ['required' => true], 
    ['required' => 'Email is required']
);

if ($objForm->isSubmitted() && $objForm->isValid()) {
    $email = $objForm->getValidField('email')->getValue();
    // Process form
} else {
    echo $objForm->toHtml();
}
```

## Agent Workflow Tips

1. **Touch source, not build artifacts** – edit files in `classes/ValidFormBuilder`, `js/`, `examples/`, etc. The `docs/` directory is regenerated via phpDocumentor; do not hand-edit it.
2. **Docblocks are part of the API** – keep `@link`, `@method`, and parameter annotations accurate. Re-run `vendor/bin/phpdoc` after modifying docblocks or public/protected methods.
3. **Meta arrays must stay in sync** – if you introduce new meta keys, update the reserved/meta arrays in `Base` so validation and rendering logic know about them.
4. **Dynamic fields need testing** – any change to cloning/counters should be exercised in `examples/test-webcomponent.php` to ensure the JS layer still works.
5. **Communicate edge cases in PR descriptions** – include reproduction steps for conditional logic or validation fixes so reviewers can verify quickly.
