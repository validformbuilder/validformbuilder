/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2014 Neverwoods.
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @author     Felix Langfeldt <felix@neverwoods.com>
 * @author     Robin van Baalen <robin@neverwoods.com>
 * @license    https://github.com/neverwoods/validformbuilder/blob/master/LICENSE
 * @link       https://github.com/neverwoods/validformbuilder
 ***************************/

/**
 * ValidForm class
 * @param {String} strFormId            The form ID
 * @param {String} strMainAlert         The main form alert
 */
function ValidForm(strFormId, strMainAlert) {
	if (strFormId !== undefined) {
        this.id                     = strFormId;
	    this.elements               = {};
        this.pages                  = [];
	    this.valid                  = false;
	    this.validator              = new ValidFormValidator(this.id);
	    this.validator.mainAlert    = strMainAlert;
	    this.events                 = [];
	    this.cachedEvents           = [];
	    this.conditions             = [];
	    this.customEvents           = [
                                        "beforeSubmit",
                                        "beforeNextPage",
                                        "afterNextPage",
                                        "beforePreviousPage",
                                        "afterPreviousPage",
                                        "beforeAddPreviousButton",
                                        "afterAddPreviousButton",
                                        "beforeShowPage",
	                                    "afterShowPage",
                                        "beforeAddPageNavigation",
                                        "afterAddPageNavigation",
                                        "beforeDynamicChange",
                                        "afterDynamicChange",
                                        "afterValidate"
                                    ];
	    this.labels                 = {};
	    this.classes                = {};
	    this.__continueExecution    = true;

        // Initialize ValidForm class
	    this._init();
	}
}

/**
 * Initialize ValidForm Builder client side after all elements are added to the collection.
 */
ValidForm.prototype._init = function() {
    var self = this;

    // Handle disabled elements and make sure all sub-elements are disabled as well.
    this.traverseDisabledElements();

    // This is where the magic happens: onSubmit; validate form.
    jQuery("#" + self.id).bind("submit", function(){
        jQuery("#" + this.id).trigger("VF_BeforeSubmit", [{ValidForm: self}]);
        if (typeof self.events.beforeSubmit == "function") {
            self.events.beforeSubmit(self);
        }

        if (self.__continueExecution) {
            if (self.pages.length > 1) {
                // Validation has been done on each page individually.
                // No need to re-validate here.
                return true;
            } else {
                return self.validate();
            }
        } else {
            return false;
        }
    });

    // Dynamic duplication logic.
    self.dynamicDuplication();
};

ValidForm.prototype.initialize = function () {
    var self = this;

    for (var i = 0; i <= self.conditions.length; i++) {
        if (typeof self.conditions[i] !== "undefined") {
            self.conditions[i]._init();
        }
    }
};

ValidForm.prototype.addCondition = function (objCondition) {
    this.conditions.push(new ValidFormCondition(this, objCondition));
};

/**
 * Parse field errors from javascript object as such:
 *
 * [
 *      {fieldName: "Error message here"},
 *      {fieldName: "Error message here"},
 *      .... etc.
 * ]
 *
 * This enables us to push validation errors from ajax return objects.
 *
 * @param  {object} objFields The fields object which contains fieldname-error pairs
 */
ValidForm.prototype.showAlerts = function (objFields) {
    var __this = this;

    //*** Remove open alerts first.
    __this.removeAlerts();

    try {
        if ($(objFields).length > 0) {
            $(objFields).each(function () {
                var objFieldError = this;

                for (var fieldName in objFieldError) {
                    if (objFieldError.hasOwnProperty(fieldName)) {
                        var objField = __this.getElement(fieldName);

                        if (objField !== null) {
                            // Field found in current form
                            objField.validator.showAlert(objFieldError[fieldName]);
                        }
                    }
                }
            });

            $("#" + __this.id).trigger("VF_ShowAlerts", [{ValidForm: __this, invalidFields: objFields}]);
        }
    } catch (e) {
        try {
            console.error("Show alerts failed: ", e.message, e); // Log error
        } catch (err) {} // Or die trying
    }
};

ValidForm.prototype.removeAlerts = function() {
    for (var strElement in this.elements) {
        var objElement = this.elements[strElement];
        if (objElement !== null) {
            objElement.validator.removeAlert();
        }
    }
};

ValidForm.prototype.setLabel = function (key, value) {
    if (typeof value !== "undefined") {
        this.labels[key] = value;
    } else {
        throw new Error("Cannot set empty label in ValidForm.setLabel('" + key + "', '" + value + "')");
    }
};

ValidForm.prototype.setClass = function (key, value) {
    if (typeof value !== "undefined") {
        this.classes[key] = value;
    } else {
        throw new Error("Cannot set empty class in ValidForm.setClass('" + key + "', '" + value + "')");
    }
};

ValidForm.prototype.matchfields = function (strSecondFieldId, strFirstFieldId, strMatchError) {
    var objElement = this.getElement(jQuery("#" + strSecondFieldId).attr("name"));
    objElement.validator.matchWith = this.getElement(jQuery("#" + strFirstFieldId).attr("name"));
    objElement.validator.matchError = strMatchError;
};

ValidForm.prototype.traverseDisabledElements = function () {
    var __this = this;

    jQuery("#" + this.id + " fieldset.vf__disabled").each(function(){
        var fieldset = this;

        jQuery("input, select, textarea", fieldset).attr("disabled", "disabled");
        jQuery(".vf__dynamic a", fieldset).addClass("vf__disabled");
        jQuery("legend input", fieldset)
            .removeAttr("disabled");
    });

    jQuery("#" + this.id + " .vf__area > legend input[type='radio'], #" + this.id + " .vf__area > legend input[type='checkbox']").each(function(){
        __this.attachAreaEvents(jQuery(this));
    });
};

ValidForm.prototype.dynamicDuplication = function () {
    var __this  = this;

    // Bind click event to duplicate button
    jQuery(".vf__dynamic a").bind("click", function() {
        var $anchor = jQuery(this);
        var $dynamicDuplicationWrap = $anchor.closest("div.vf__dynamic");

        //*** Call custom event if set.
        jQuery("#" + __this.id).trigger("VF_BeforeDynamicChange", [{
            ValidForm: __this,
            objAnchor: $anchor,
            objOriginal: $dynamicDuplicationWrap.prev()
        }]);

        if (typeof __this.events.beforeDynamicChange == "function") {
            __this.events.beforeDynamicChange(__this, $anchor);
        }

        //*** Stop if this flag is false
        if (!__this.__continueExecution) {
            return;
        }


        if (!$dynamicDuplicationWrap.prev().hasClass("vf__disabled")) {
            //*** Update dynamic field counter.
            var $original   = $dynamicDuplicationWrap.prev();
            var copy        = $original.clone();
            var counter; // Counter placeholder

            //*** Clear values.
            var names = jQuery(this).data("target-name").split("|");
            var ids = jQuery(this).data("target-id").split("|");

            copy.find("input[name$='_dynamic']").remove();

            jQuery.each(names, function(index, fieldname){
                var blnHasBrackets = fieldname.indexOf("[]") > -1;
                var objOriginalElement = __this.getElement(fieldname);
                var objNewElement = jQuery.extend(new ValidFormElement(), objOriginalElement);

                //*** Clear fieldname from brackets
                fieldname = fieldname.replace("[]", "");

                //*** Set counter variable on current counter object
                counter = $("#" + fieldname + "_dynamic");

                //*** Set value to number '0' if value is NaN
                var counterValue = parseInt(counter.val());
                if (isNaN(counterValue)) {
                    counterValue = 0;
                    counter.val(counterValue);
                }

                //*** We've duplicated an element, add one to the counter
                counterValue = counterValue + 1;
                counter.val(counterValue);

                var search;
                if (counterValue == 1) {
                    search = fieldname;
                } else {
                    search = fieldname + "_" + (counterValue - 1);

                    if (blnHasBrackets) {
                        search += "[]";
                    }
                }

                //*** Add fields to the form collection.
                if (objOriginalElement !== null) { // objOriginalElement is null if there is a paragraph in the area.
                    objNewElement.id = ids[index] + "_" + counter.val();
                    objNewElement.name = fieldname + "_" + counter.val();
                    objNewElement.validator = jQuery.extend(new ValidFormFieldValidator(), objOriginalElement.validator);
                    objNewElement.validator.id = objNewElement.id;
                    objNewElement.validator.required = false;

                    __this.addElement(objNewElement);
                }

                copy.find("[name='" + search + "']").each(function() {
                    var $field = jQuery(this);

                    if ($field.attr("type") == "radio" ||
                        $field.attr("type") == "checkbox"
                    ) {
                        var suffix = '';
                        if ($field.prop("type") === "checkbox") {
                            suffix = '[]';
                        }

                        //*** Radio buttons and checkboxes have to be treated differently.
                        var fieldId;
                        if (counterValue == 1) {
                            fieldId = $field.attr("id");
                        } else {
                            var arrFieldId = $field.attr("id").split("_");
                            arrFieldId.pop();
                            fieldId = arrFieldId.join("_");
                        }

                        var fieldId = fieldId + "_" + counterValue;
                        $field
                            .removeAttr("checked")
                            .attr("name", fieldname + "_" + counterValue + suffix)
                            .attr("id", fieldId)
                            .parent("label")
                                .attr("for", fieldId);
                    } else if ($field.is("select")) {
                        //*** Special 'select' treatment
                        $field
                            .attr("name", fieldname + "_" + counter.val())
                            .attr("id", ids[index] + "_" + counter.val())
                            .prev("label").attr("for", ids[index] + "_" + counter.val());

                        objNewElement.setValue(objOriginalElement.getDefaultValue(), $field);
                    } else {
                        //*** Normal fields (input, textarea) are easy.
                        $field
                            .val(objOriginalElement.getDefaultValue()) // = hint or empty
                            .attr("name", fieldname + "_" + counter.val())
                            .attr("id", ids[index] + "_" + counter.val())
                            .prev("label").attr("for", ids[index] + "_" + counter.val());

                        $field
                            .bind("focus.validform-hint", function() {
                                if ($field.val() == objOriginalElement.validator.hint) {
                                    $field.val("");
                                    $field
                                        .closest(".vf__hint")
                                        .removeClass("vf__hint");
                                }
                            })
                            .bind("blur.validform-hint", function() {
                                if (objOriginalElement.validator.hint && $field.val() === "") {
                                    $field.val(objOriginalElement.validator.hint);
                                    $field.parent().addClass("vf__hint");
                                } else {
                                    $field
                                        .closest(".vf__hint")
                                        .removeClass("vf__hint");
                                }
                            });

                        $field.triggerHandler("focus.validform-hint");
                        $field.triggerHandler("blur.validform-hint");
                    }
                });

            });

            //*** Fix multifields in areas.
            if (typeof counter == "object" && copy.hasClass("vf__area")) {
                copy.find(".vf__multifield").each(function(){
                    if (counter.val() == 1) {
                        fieldId = jQuery(this).attr("id");
                    } else {
                        var arrFieldId = jQuery(this).attr("id").split("_");
                        arrFieldId.pop();
                        fieldId = arrFieldId.join("_");
                    }

                    jQuery(this).attr("id", fieldId + "_" + counter.val());
                });
            }

            //*** Remove 'required' styling.
            copy
                .find(".vf__required")
                .removeClass("vf__required")
                .addClass("vf__optional");
            copy
                .removeClass("vf__required")
                .removeClass("vf__error")
                .addClass("vf__optional")
                .addClass("vf__clone");

            // Increse the cloned element's ID with the counter value
            if (typeof copy.prop("id") !== "undefined" && copy.prop("id") !== "") {
                if (counter.val() == 1) {
                    fieldId = copy.attr("id");
                } else {
                    var arrFieldId = copy.attr("id").split("_");
                    arrFieldId.pop();
                    fieldId = arrFieldId.join("_");
                }
                copy.attr("id", fieldId + "_" + counter.val());
            }

            // Remove errors
            copy.find("p.vf__error").remove();
            copy.find(".vf__error").removeClass("vf__error");

            //*** Fix click event on active areas.
            if (copy.hasClass("vf__area")) {
                var copiedTrigger = jQuery("legend :checkbox", copy);
                var originalTrigger = jQuery("legend :checkbox", $original);

                if (copiedTrigger.length > 0) {
                    counter = $("#" + copiedTrigger.attr("name") + "_dynamic");

                    // +1 on the counter
                    counter.val(parseInt(counter.val()) + 1);

                    copiedTrigger.attr("id", copiedTrigger.attr("id") + "_" + counter.val());
                    copiedTrigger.attr("name", copiedTrigger.attr("name") + "_" + counter.val());
                    copiedTrigger.parent("label").attr("for", copiedTrigger.attr("id"));

                    if (originalTrigger.attr("checked") == "checked") {
                        copiedTrigger.attr("checked", "checked");
                    }

                    __this.attachAreaEvents(copiedTrigger);
                }
            }

            // Add copy to DOM
            jQuery(this).parent().before(copy);

            //*** Fix conditions that might be attached to the original elements.
            var copyName = copy.prop("name");
            if (typeof counter == "object"
                && typeof copyName !== "undefined"
                && !__this.inArray(names, copyName)
            ) {
                __this.attachDynamicConditions(names, counter.val());
            }

            //*** Call custom event if set.
            jQuery("#" + __this.id).trigger("VF_AfterDynamicChange", [{ValidForm: __this, objAnchor: $anchor, objCopy: copy, objOriginal: $original, count: counter.val()}]);
            if (typeof __this.events.afterDynamicChange == "function") {
                __this.events.afterDynamicChange({ValidForm: __this, objAnchor: $anchor, objCopy: copy, objOriginal: $original, count: counter.val()});
            }
        }

        return false;
    });
};

ValidForm.prototype.attachDynamicConditions = function(arrElementNames, dynamicCount) {
    var self = this,
        j = 0, // Used as a counter inside the for loop.
        comparisons;

    for (var i = 0; i <= self.conditions.length; i++) {
        var condition = self.conditions[i];

        if (typeof condition !== "undefined") {
            var blnInDynamic = false;
            var newCondSubject = condition.subject.name;

            if (!newCondSubject) {
                //*** Probably a multifield.
                if (condition.subject instanceof jQuery) {
                    for (var elementNameIndex in arrElementNames) {
                        if (arrElementNames.hasOwnProperty(elementNameIndex)) {
                            if (condition.subject.find("[name='" + arrElementNames[elementNameIndex] + "']").length > 0) {
                            blnInDynamic = true;
                            newCondSubject = condition.subject.attr("id") + "_" + dynamicCount;

                                break;
                        }
                }
                    }
                }
            } else {
                //*** Regular field (ValidFormElement).
                if ($.inArray(newCondSubject, arrElementNames) > -1) {
                    blnInDynamic = true;
                    newCondSubject = newCondSubject + "_" + dynamicCount;
                } else {
                    comparisons = condition.comparisons;
                    for (j = 0; j < comparisons.length; j++) {
                        if ($.inArray(comparisons[j].subject.name, arrElementNames) > -1) {
                            blnInDynamic = true;
                            break;
                        }
                    }
                }
            }

            if (blnInDynamic) {
                var arrComparisons = [];

                comparisons = condition.comparisons;
                for (j = 0; j < comparisons.length; j++) {
                    var newCompSubject = comparisons[j].subject.name;

                    if ($.inArray(comparisons[j].subject.name, arrElementNames) > -1) {
                        newCompSubject = comparisons[j].subject.name + "_" + dynamicCount;
                    }

                    arrComparisons.push({
                        "subject": newCompSubject,
                        "comparison": comparisons[j].comparison,
                        "value": comparisons[j].value
                    });
                }

                var newCondition = new ValidFormCondition(self, {
                    "subject": newCondSubject,
                    "property": condition.property,
                    "value": condition.value,
                    "comparisonType": condition.comparisonType,
                    "comparisons": arrComparisons
                });
                newCondition._init();
            }
        }
    }
};

ValidForm.prototype.attachAreaEvents = function(objActiveTrigger) {
    var __this = this,
        inputNames = [];

    objActiveTrigger.unbind("change").bind("change", function(){
        var self = this;

        var fieldsets = jQuery("input[name='" + jQuery(this).attr("name") + "']").closest(".vf__area");
        var currentFieldset = jQuery(objActiveTrigger).closest(".vf__area");
        fieldsets.each(function(){
            var fieldset = jQuery(this),
                $dynamicTrigger;

            if (fieldset[0] == currentFieldset[0]) {
                if (self.checked) {
                    // Enable active area
                    jQuery("input, select, textarea", currentFieldset).removeAttr("disabled");
                    jQuery(".vf__dynamic a", currentFieldset).removeClass("vf__disabled");
                    jQuery(currentFieldset).removeClass("vf__disabled");

                    $dynamicTrigger = jQuery(currentFieldset).data("vf__dynamicTrigger");
                    if (typeof $dynamicTrigger === "object") {
                        $dynamicTrigger.show();
                    }

                    /*
                     * Walk through the conditions to "reset" conditions that have been overruled by the code above.
                     * TODO: Only check comparisons that are part of this area.
                     */
                    for (var i = 0; i <= __this.conditions.length; i++) {
                        if (typeof __this.conditions[i] !== "undefined") {
                            var condition = __this.conditions[i];
                            for (var j = 0; j <= condition.comparisons.length; j++) {
                                if (typeof condition.comparisons[j] !== "undefined") {
                                    var subject = condition.comparisons[j].subject;
                                    if (subject) {
                                        $("[name='" + subject.name + "']").triggerHandler("change");
                                    }
                                }
                            }
                        }
                    }

                    $("#" + __this.id).trigger("VF_EnableActiveArea", [{ValidForm: __this, objArea: currentFieldset}]);
                } else {
                    // Disable active area & remove error's
                    inputNames = [];

                    jQuery("div > input, select, textarea", currentFieldset)
                        .attr("disabled", "disabled")
                        .each(function () {
                            inputNames.push($(this).attr("name"));
                        });

                    jQuery(".vf__dynamic a", currentFieldset).addClass("vf__disabled");
                    jQuery("legend input", currentFieldset).removeAttr("disabled");
                    jQuery(currentFieldset).addClass("vf__disabled");

                    // Get the dynamic trigger, if available
                    $dynamicTrigger = $("[data-target-id='" + inputNames.join("|") + "']");
                    if ($dynamicTrigger.length > 0) {
                        $dynamicTrigger.hide();

                        // And store it in a data attribute of the current fieldset for later reference.
                        jQuery(currentFieldset).data("vf__dynamicTrigger", $dynamicTrigger);
                    }

                    //*** Remove errors.
                    jQuery("div.vf__error", currentFieldset).each(function(){
                        jQuery(this).removeClass("vf__error").find("p.vf__error").remove();
                    });

                    $("#" + __this.id).trigger("VF_DisableActiveArea", [{ValidForm: __this, objArea: currentFieldset}]);
                }
            } else {
                // Disable active area & remove error's
                inputNames = [];

                jQuery("div > input, select, textarea", fieldset)
                    .attr("disabled", "disabled")
                    .each(function () {
                        inputNames.push($(this).attr("name"));
                    });

                jQuery(".vf__dynamic a", fieldset).addClass("vf__disabled");
                jQuery("legend input", fieldset).removeAttr("disabled");
                jQuery(fieldset).addClass("vf__disabled");

                // Get the dynamic trigger, if available
                $dynamicTrigger = $("[data-target-id='" + inputNames.join("|") + "']");
                if ($dynamicTrigger.length > 0) {
                    $dynamicTrigger.hide();

                    // And store it in a data attribute of the current fieldset for later reference.
                    jQuery(fieldset).data("vf__dynamicTrigger", $dynamicTrigger);
                }

                //*** Remove errors.
                jQuery("div.vf__error", fieldset).each(function(){
                    jQuery(this).removeClass("vf__error").find("p.vf__error").remove();
                });

                $("#" + __this.id).trigger("VF_DisableActiveArea", [{ValidForm: __this, objArea: fieldset}]);
            }
        });
    });
};

ValidForm.prototype.inArray = function(arrToSearch, value) {
    for (var i=0; i < arrToSearch.length; i++) {
        if (arrToSearch[i] === value) {
            return true;
        }
    }
    return false;
};

ValidForm.prototype.addElement = function() {
    var objAddedElement = null;
    var strElementName = "";

    if (arguments.length > 0 && typeof(arguments[0]) == "object") {
        strElementName = arguments[0].name;
        objAddedElement = this.elements[arguments[0].name] = arguments[0];
    } else {
        var typeError       = "";
        var required        = false;
        var requiredError   = "";
        var hint            = null;
        var hintError       = "";
        var minLength       = null;
        var minLengthError  = "";
        var maxLength       = null;
        var maxLengthError  = "";
        var strElementId = null;
        var strValidation = null;

        if (arguments.length > 0) {
            strElementId = arguments[0];
        } else {
            return false;
        }

        if (arguments.length > 1) {
            strElementName = arguments[1];
        } else {
            return false;
        }

        if (arguments.length > 2) {
            strValidation = arguments[2];
        } else {
            return false;
        }

        if (arguments.length > 3) {
            required = arguments[3];
        }

        if (arguments.length > 4) {
            maxLength = arguments[4];
        }

        if (arguments.length > 5) {
            minLength = arguments[5];
        }

        if (arguments.length > 6) {
            hint = arguments[6];
        }

        if (arguments.length > 7) {
            typeError = arguments[7];
        }

        if (arguments.length > 8) {
            requiredError = arguments[8];
        }

        if (arguments.length > 9) {
            hintError = arguments[9];
        }

        if (arguments.length > 10) {
            minLengthError = arguments[10];
        }

        if (arguments.length > 11) {
            maxLengthError = arguments[11];
        }

        objAddedElement = this.elements[strElementName] = new ValidFormElement(this.id, strElementName, strElementId, strValidation, required, maxLength, minLength, hint, typeError, requiredError, hintError, minLengthError, maxLengthError);
    }

    // Store the element in a data property in the DOM element.
    $("[name='" + strElementName + "']", "#" + this.id).data("vf__field", objAddedElement);

    return objAddedElement;
};

ValidForm.prototype.getElement = function(strElementName){
    var objReturn = null;

    for (var strElement in this.elements) {
        if (strElement == strElementName) {
            objReturn = this.elements[strElement];
            break;
        }
    }

    return objReturn;
};

ValidForm.prototype.addEvent = function(strEvent, callback){
    if (this.inArray(this.customEvents, strEvent)) {
        this.events[strEvent] = callback;

        for(var i in this.cachedEvents) {
            if (this.cachedEvents.hasOwnProperty(i)) {
                var objCachedEvent = this.cachedEvents[i];
                for (var eventName in objCachedEvent) {
                    if (objCachedEvent.hasOwnProperty(eventName)) {
                        if (strEvent == eventName) {
                            this.events[strEvent](objCachedEvent[eventName]);
                        }
                    }
                }
            }
        }
    } else {
        jQuery("#" + this.id).bind(strEvent, callback);
    }
};

ValidForm.prototype.reset = function() {
    this.validator.removeMain();
    for (var strElement in this.elements) {
        var objElement = this.elements[strElement];

        // Only reset if this is an element that can be reset.
        if (typeof objElement.reset == "function") {
            objElement.reset();
        }
    }
};

/**
 * Validate method
 * Uses the ValidForms, ValidForm, ValidElement and ValidFormAlerter
 * objects to validate form elements.
 *
 * @param  {String} strSelector Optional selector to specify validation limits
 * @return {Boolean}             True if all fields are valid, false if not.
 */
ValidForm.prototype.validate = function(strSelector) {
    this.valid = true;
    var objDOMForm = null;
    var blnReturn = false;
    strSelector = strSelector || null;

    //*** Set the form object.
    try {
        objDOMForm = jQuery("#" + this.id);
    } catch(e) {
        alert("An error occured while calling the Form.\nMessage: " + e.message);
        this.valid = false;
    }

    if (objDOMForm) {
        //*** Reset main error notifications.
        this.validator.removeMain();
        this.validator.removePage();

        for (var strElement in this.elements) {
            var objElement = this.elements[strElement];

            if ((
                    (strSelector !== null) &&
                    (jQuery(strSelector).has(jQuery("[name='" + objElement.name + "']")).length > 0)
                ) ||
                (strSelector === null)
            ) {
                //*** Check if the element is part of an area.
                var objArea = jQuery("[name='" + objElement.name + "']").parentsUntil(".vf__area").parent(".vf__area");
                if (objArea.length > 0) {
                    var objChecker = jQuery("legend :checkbox", objArea);
                    if (objChecker.length > 0) {
                        if (objChecker.get(0).checked) {
                            if (!objElement.validate()) {
                                this.valid = false;
                            }
                        }
                    } else {
                        if (!objElement.validate()) {
                            this.valid = false;
                        }
                    }
                } else {
                    if (!objElement.validate()) {
                        this.valid = false;
                    }
                }
            }
        }
    } else {
        alert("An error occured while calling the Form.\nMessage: " + e.message);
        this.valid = false;
    }

    if (!this.valid) {
        this.validator.showMain();
    }

    blnReturn = this.valid;

    jQuery("#" + this.id).trigger("VF_AfterValidate", [{ValidForm: this, selector: strSelector}]);
    if (typeof this.events.afterValidate == "function") {
        varReturn = this.events.afterValidate(this, strSelector);
        if (typeof varReturn !== "undefined") {
            blnReturn = varReturn;
        }
    }

    return blnReturn;
};

function ValidFormComparison (objForm, subject, comparison, value) {
    this.subject    = this._setSubject(objForm, subject);
    if (this.subject !== null) {
        this.comparison = comparison;
        this.value      = value || null;
        this.isMet      = false;

        this._deferred  = $.Deferred();

        this.uid        = "com_" + Math.floor(Math.random()*9999);

        return this._init();
    } else {
        return false;
    }
}

ValidFormComparison.prototype._init = function () {
    try {
        var self = this,
        $objSubject = (this.subject instanceof jQuery) ? this.subject : $("[name='" + this.subject.name + "']");

        //*** Set event listeners. Trigger 'change' event
        if ($objSubject.is("input") || $objSubject.is("textarea")) {
            if ($objSubject.attr("type") !== "checkbox" && $objSubject.attr("type") !== "radio") {

                var delay = null;
                $objSubject.on("keyup", function () {
                    var $self = $(this);

                    clearTimeout(delay);
                    delay = setTimeout(function () {
                        $self.triggerHandler("change");
                    }, 300);
                });
            }
        }

        $objSubject
            .on("change", function () {
                self._deferred.notifyWith(self, [self.check()]);
            })
            .triggerHandler("change"); // make sure conditions are met onload

    } catch (e) {
        throw new Error("Failed to initialize ValidFormComparison: " + e.message, 1);
    }

    this.promise    = this._deferred.promise();

    return this;
};

ValidFormComparison.prototype._setSubject = function (objForm, strSubject) {
    var varReturn;

    if (typeof objForm !== "object" || !objForm instanceof ValidForm) {
        throw new Error("ValidForm element not set in ValidFormCondition.");
    }

    try {
        varReturn = objForm.getElement(strSubject);

        if (varReturn === null) {
            // Element not found in ValidForm internal collection,
            // this is probably a fieldset, area or paragraph element.
            varReturn = $("#" + strSubject);

            if (varReturn.length <= 0) {
                varReturn = null; // Reset subject
                //throw new Error("Could not find subject element with id or name '" + strSubject + "'.", 1);
            }
        }
    } catch (e) {
        throw new Error("Failed to set subject: " + e.message, 1);
    }

    return varReturn;
};

ValidFormComparison.prototype.check = function () {
    var blnReturn = false,
        self = this,
        strValue = (self.subject instanceof jQuery) ? self.subject.val() : self.subject.getValue(),
        intCurrentValue, varCurrentValue;

    switch (self.comparison) {
        case "equal":
            if (strValue == self.value) {
                // Comparison met.
                blnReturn = true;
            }
            break;
        case "notequal":
            if (strValue != self.value) {
                // Comparison met.
                blnReturn = true;
            }
            break;
        case "empty":
            if (strValue === "") {
                blnReturn = true;
            }
            break;
        case "notempty":
            if (strValue !== "") {
                blnReturn = true;
            }
            break;
        case "lessthan":
            intCurrentValue = parseInt(strValue);
            varCurrentValue = (isNaN(intCurrentValue)) ? strValue : intCurrentValue;

            if (varCurrentValue < self.value) {
                blnReturn = true;
            }
            break;
        case "greaterthan":
            intCurrentValue = parseInt(strValue);
            varCurrentValue = (isNaN(intCurrentValue)) ? strValue : intCurrentValue;

            if (varCurrentValue > self.value) {
                blnReturn = true;
            }
            break;
        case "lessthanorequal":
            intCurrentValue = parseInt(strValue);
            varCurrentValue = (isNaN(intCurrentValue)) ? strValue : intCurrentValue;

            if (varCurrentValue <= self.value) {
                blnReturn = true;
            }
            break;
        case "greaterthanorequal":
            intCurrentValue = parseInt(strValue);
            varCurrentValue = (isNaN(intCurrentValue)) ? strValue : intCurrentValue;

            if (varCurrentValue >= self.value) {
                blnReturn = true;
            }
            break;
        case "contains":
            if (strValue.toString().toLowerCase().indexOf(self.value.toString().toLowerCase()) !== -1) {
                blnReturn = true;
            }
            break;
        case "doesnotcontain":
            if (strValue.toString().toLowerCase().indexOf(self.value.toString().toLowerCase()) === -1) {
                blnReturn = true;
            }
            break;
        case "startswith":
            if (strValue.indexOf(self.value) === 0) {
                blnReturn = true;
            }
            break;
        case "endswith":
            if (strValue.slice(-self.value.length) == self.value) {
                blnReturn = true;
            }
            break;
        case "regex":
            var strRexEx = self.value.replace('\/', '').replace('\/i', ''),
                objRegEx = new RegExp(strRexEx);

            blnReturn = objRegEx.test(strValue);
            break;
    }

    self.isMet = blnReturn;

    return blnReturn;
};

function ValidFormCondition (objForm, objCondition) {
    if (typeof objCondition !== "object" || objCondition === null) {
        throw new Error("Invalid condition object supplied in ValidFormCondition construct.");
    }

    if (typeof objForm === "undefined" || !(objForm instanceof ValidForm)) {
        throw new Error("Form object undefined or not an instance of ValidForm in ValidFormCondition construct.");
    }

    try {
        this.validform      = objForm;
        this.subject        = this._setSubject(objCondition.subject);
        this.property       = objCondition.property;
        this.value          = objCondition.value;
        this.comparisonType = objCondition.comparisonType;
        this.comparisons    = [];
        this.condition      = objCondition;

    } catch (e) {
        throw new Error("Failed to set default values in ValidFormCondition construct: " + e.message);
    }

    return this;
}

ValidFormCondition.prototype._init = function () {
    try {
        var self = this,
            objComparisons = this.condition.comparisons;

        if (typeof objComparisons === "object" && objComparisons.length > 0) {
            for (var i = 0; i < objComparisons.length; i++) {
                var Comparison = objComparisons[i];
                if (this.validform.getElement(Comparison.subject) !== null) {
                    this.addComparison(new ValidFormComparison(this.validform, Comparison.subject, Comparison.comparison, Comparison.value));
                }
            }
        }

        self.isMet()
            .progress(function (blnResult) {
                self.set(blnResult);
            });

    } catch (e) {
        throw new Error("Failed to initialize Condition: " + e.message, 1);
    }

    return this;
};

ValidFormCondition.prototype._setSubject = function (strSubject) {
    var varReturn;

    if (typeof this.validform !== "object" || !this.validform instanceof ValidForm) {
        throw new Error("ValidForm element not set in ValidFormCondition.");
    }

    try {
        varReturn = this.validform.getElement(strSubject);

        if (varReturn === null) {
            // Element not found in ValidForm internal collection,
            // this is probably a fieldset, area or paragraph element.
            varReturn = $("#" + strSubject);

            if (varReturn.length <= 0) {
                throw new Error("Could not find subject element with id or name '" + strSubject + "'.", 1);
            }
        }
    } catch (e) {
        throw new Error("Failed to set subject: " + e.message, 1);
    }

    return varReturn;
};

ValidFormCondition.prototype.set = function (blnResult) {
    var self = this;

    //*** Utility functions
    var Util = {
        "visible": function (blnValue) {
            var $objSubject = (self.subject instanceof jQuery) ? self.subject : $("[name='" + self.subject.name + "']");
            var $objBase = $objSubject;

            if (blnValue) {
                $objSubject.show();

                if (!$objSubject.is("div") && !$objSubject.is("fieldset")) {
                    // $objBase = $objSubject.parent(); // Original
                    $objBase = $objSubject.closest("div.vf__optional, div.vf__required, div.vf__multifielditem");
                    $objBase.show();
                }

                if ($objSubject.attr("type") == "checkbox" || $objSubject.attr("type") == "radio") {
                    $objSubject.closest("div").show();
                }

                if ($objBase.next().hasClass("vf__dynamic")) {
                    $objBase.next().show();
                }

                $objSubject.nextAll(".vf__clone, .vf__dynamic").show();

                // Set enabled back to default state
                Util.enabled(null, true);

                // Set required back to default state
                Util.required(null, true);

            } else {
                $objSubject.hide();

                if (!$objSubject.is("div") && !$objSubject.is("fieldset")) {
                    // $objBase = $objSubject.parent(); // Original
                    $objBase = $objSubject.closest("div.vf__optional, div.vf__required, div.vf__multifielditem");
                    $objBase.hide();
                }

                if ($objSubject.attr("type") == "checkbox" || $objSubject.attr("type") == "radio") {
                    $objSubject.closest("div").hide();
                }

                if ($objBase.next().hasClass("vf__dynamic")) {
                    $objBase.next().hide();
                }

                $objSubject.nextAll(".vf__clone, .vf__dynamic").hide();

                // Set enabled back to default state
                Util.enabled(false);

                // Set required back to default state
                Util.required(false);
            }

            $("#" + self.validform.id).trigger("VF_ConditionSet", [{"condition": "visible", "value": blnValue, "subject": self.subject}]);
        },

        "enabled": function (blnValue, blnDefaultState) {
            blnDefaultState = blnDefaultState || false;

            if (self.subject instanceof ValidFormElement) {
                blnValue = (blnDefaultState) ? self.subject.getEnabled(true) : blnValue;

                self.subject.setEnabled(blnValue);
            } else {
                // Iterate over sub elements
                $("input, textarea, select", self.subject).each(function () {
                    var objElement = self.validform.getElement($(this).attr("name"));

                    if (objElement !== null) {
                        blnValue = (blnDefaultState) ? objElement.getEnabled(true) : blnValue;
                        objElement.setEnabled(blnValue);
                    }
                });
            }

            $("#" + self.validform.id).trigger("VF_ConditionSet", [{"condition": "enabled", "value": blnValue, "subject": self.subject}]);
        },

        "required": function (blnValue, blnDefaultState) {
            blnDefaultState = blnDefaultState || false;

            if (self.subject instanceof ValidFormElement) {
                blnValue = (blnDefaultState) ? self.subject.getRequired(true) : blnValue;

                self.subject.setRequired(blnValue);
            } else {
                // Iterate over sub elements
                $("input, textarea, select", self.subject).each(function () {
                    var objElement = self.validform.getElement($(this).attr("name"));

                    if (objElement !== null) {
                        blnValue = (blnDefaultState) ? objElement.getRequired(true) : blnValue;
                        objElement.setRequired(blnValue);
                    }
                });

            }

            $("#" + self.validform.id).trigger("VF_ConditionSet", [{"condition": "required", "value": blnValue, "subject": self.subject}]);
        }
    };

    // Set the condition
    Util[self.property]((blnResult === self.value));
};

ValidFormCondition.prototype.addComparison = function (objComparison) {
    if (!objComparison instanceof ValidFormComparison) {
        throw new Error("Invalid argument: objComparison is no ValidFormComparison type in ValidFormCondition.addCondition()", 1);
    }

    this.comparisons.push(objComparison);
};

ValidFormCondition.prototype.isMet = function () {
    var self = this,
        def = $.Deferred();

    // Count met comparisons
    var _checkComparisons = function () {
        var success = 0;
        for (var i = 0; i < self.comparisons.length; i++) {
            if (self.comparisons[i].isMet) {
                if (success < self.comparisons.length) {
                    success++;
                } else {
                    if (success > 0) {
                        success--;
                    }
                }
            }
        }

        return success;
    };

    // comparison match all
    var _matchAll = function () {
        return _checkComparisons() === self.comparisons.length;
    };

    // comparison match any
    var _matchAny = function () {
        return _checkComparisons() > 0;
    };

    var matchAnyProgressCallback = function (blnResult) {
                if (_matchAll()) {
                    def.notify(true);
                } else {
                    def.notify(false);
                }
    };

    var matchAllProgressCallback = function (blnResult) {
                if (_matchAny()) {
                    def.notify(true);
                } else {
                    def.notify(false);
                }
    };

    for (var i = 0; i < self.comparisons.length; i++) {
        if (self.comparisonType === "all") {
            self.comparisons[i].promise.progress(matchAnyProgressCallback);
        } else {
            self.comparisons[i].promise.progress(matchAllProgressCallback);
        }
    }

    return def.promise();
};

/**
 * ValidFormElement Class
 * Holds an element that can be validated
 * @param {String} strFormId      Form ID
 * @param {String} strElementName Form element name
 * @param {String} strElementId   Form element ID
 * @param {String} strValidation  Validation regular expression
 */
function ValidFormElement(strFormId, strElementName, strElementId, strValidation) {
    this.formId                 = strFormId;
    this.id                     = strElementId;
    this.name                   = strElementName;
    this.disabled             = ($("#" + strElementId).attr("disabled") === "disabled");
    this.validator              = new ValidFormFieldValidator(strElementId, strElementName);
    this.validator.check        = strValidation;
    this.validator.required     = false;
    this.validator.minLength    = null;
    this.validator.maxLength    = null;

    // Rewritten 'ValidFormElement.arguments' to just 'arguments' -Robin
    // http://aptana.com/reference/api/Arguments.html
    if (arguments.length > 4) {
        this.validator.required = arguments[4];
    }

    if (arguments.length > 5) {
        this.validator.maxLength = arguments[5];
    }

    if (arguments.length > 6) {
        this.validator.minLength = arguments[6];
    }

    if (arguments.length > 7) {
        this.validator.hint = arguments[7];

        var __this = this;
        if (this.validator.hint !== "") {
            jQuery("#" + this.id)
                .bind("focus", function(){
                    if (jQuery(this).val() == __this.validator.hint) {
                        jQuery(this).val("");
                        jQuery(this).closest(".vf__hint").removeClass("vf__hint");
                    }
                })
                .bind("blur", function(){
                    if (jQuery(this).val() === "") {
                        jQuery(this).val(__this.validator.hint);
                        jQuery(this).parent().addClass("vf__hint");
                    } else {
                        jQuery(this).closest(".vf__hint").removeClass("vf__hint");
                    }
                });
        }
    }

    if (arguments.length > 8) {
        this.validator.typeError = arguments[8];
    }

    if (arguments.length > 9) {
        this.validator.requiredError = arguments[9];
    }

    if (arguments.length > 10) {
        this.validator.hintError = arguments[10];
    }

    if (arguments.length > 11) {
        this.validator.minLengthError = arguments[11];
    }

    if (arguments.length > 12) {
        this.validator.maxLengthError = arguments[12];
    }

    // Keep the original values in a local cache for future reference.
    this._defaultstate = {
        "required": this.validator.required,
        "enabled": !this.disabled,
        "value": this.validator.defaultValue
    };
}

ValidFormElement.prototype.setHintValue = function (value) {
    if (this.getValue() === this.getHintValue()) {
        // Update hint value
        this.setValue(value);
    }

    this.validator.hint = value;
};

ValidFormElement.prototype.getHintValue = function () {
    return this.validator.hint;
};

ValidFormElement.prototype.getValue = function () {
    return $("#" + this.id).val();
};

ValidFormElement.prototype.setValue = function (value, $element) {
    $element = $element || $("#" + this.id);

    if ($element.is("select")) {
        $element
            .find('option')
            .removeAttr('selected')
            .closest('select')
            .find('option[value="' + value + '"]')
            .attr('selected', 'selected');
    } else {
        // input || textarea
        $("#" + this.id).val(value);
    }
};

ValidFormElement.prototype.getDefaultValue = function () {
    var strReturn = this.validator.defaultValue;

    if (strReturn === "") {
        strReturn = this.getHintValue();
    }

    return strReturn;
};

/**
 * Validate this form element
 * @return {boolean} True if valid, false if not
 */
ValidFormElement.prototype.validate = function() {
    return this.validator.validate();
};

ValidFormElement.prototype.getValue = function () {
    var $field     = jQuery("[name='" + this.name + "']"),
        value;

    switch ($field.attr("type")) {
        case "radio":
            // Single value
            value = jQuery("[name='" + this.name + "']:checked").val();
            break;
        case "checkbox":
            value = jQuery("[name='" + this.name + "']:checked").map(function () {return this.value;}).get().join(", ");
            break;
        default:
            value = $field.val();
    }

    return value;
};

ValidFormElement.prototype.setRequired = function (blnValue) {
    this.validator.required = blnValue;

    // The state has changed, remove it's alert.
    this.validator.removeAlert();

    var $element = $("[name='" + this.name + "']");

    var $parent = $element.closest("div.vf__optional, div.vf__required");
    if (blnValue) {
        // Required == true
        $parent.removeClass("vf__optional").addClass("vf__required");
    } else {
        // Required == false
        $parent.addClass("vf__optional").removeClass("vf__required");
    }

    $parent.find("input, select").each(function () {
        var field = $(this).data("vf__field");

        if (typeof field !== "undefined") {
            if (field.validator.required) {
                $parent.removeClass("vf__optional").addClass("vf__required");
            }
        }
    });
};

ValidFormElement.prototype.getRequired = function (blnDefaultState) {
    return (!!blnDefaultState) ? this._defaultstate.required : this.validator.required;
};

ValidFormElement.prototype.setEnabled = function (blnValue) {
    this.disabled = !blnValue;

    var $element = $("[name='" + this.name + "']");
    var $parent = $element.closest("div.vf__optional, div.vf__required, div.vf__multifielditem");

    /**
     * FIXME: Next line is disabled because it will break on comparisons where a checkbox (boolean) is the subject.
     * Tested on multifields and normal fields and works good without it. Why was it introduced in revision d309314?
     */
    //var $parent = $element.parent();
    if ($parent.hasClass("vf__multifielditem")) {
        // Multifield item
        $parent = $parent.parent();
    }

    if (blnValue) {
        // Enabled == true, e.g. disabled = false
        $element
            .removeClass("vf__disabled")
            .prop("disabled", false);

        $parent.addClass("vf__optional").removeClass("vf__required");
    } else {
        // Enabled == false, e.g. disabled = true
        $element
            .addClass("vf__disabled")
            .prop("disabled", true);
    }
};

ValidFormElement.prototype.getEnabled = function (blnDefaultState) {
    return (!!blnDefaultState) ? this._defaultstate.enabled : !this.disabled;
};

ValidFormElement.prototype.getDynamicCount = function () {
    var self        = this
    ,   varReturn   = 0
    ,   $counter    = $("input[name='" + self.name + "_dynamic']", $("#" + self.formId)); // jQuery performs better when scoped correctly

    if ($counter.length > 0) {
        varReturn = parseInt($counter.val());

        if (isNaN(varReturn)) {
            varReturn = 0;
        }
    }

    return varReturn;
};

/**
 * Reset the form element
 * @return {void}
 */
ValidFormElement.prototype.reset = function() {
    this.validator.removeAlert();

    var objElement = jQuery("#" + this.id);
    objElement.val("");
};

/**
 * ValidFormValidator class
 * Display class used to push alerts regarding form validation
 * to the browser.
 *
 * @param {String} strFormId The form ID.
 */
function ValidFormValidator(strFormId) {
    /**
     * Form id
     * @type {String}
     */
    this.id                 = strFormId;

    /**
     * Main alert
     * @type {String}
     */
    this.mainAlert          = "";
}

/**
 * Remove main form error message
 * @return {void}
 */
ValidFormValidator.prototype.removeMain = function() {
    jQuery("#" + this.id + " div.vf__main_error").remove();
};

ValidFormValidator.prototype.showMain = function(strMessage) {
    var strMainError = (typeof strMessage !== "undefined" && strMessage.length > 0) ? strMessage : this.mainAlert;

    if (strMainError !== "undefined" && strMainError.length > 0) {
        jQuery("#" + this.id).prepend("<div class=\"vf__main_error\"><p>" + strMainError + "</p></div>");
    }

    //*** Jump to the first error.
    var $objError = jQuery("div.vf__error:first");
    if ($objError.length > 0) {
        jQuery.scrollTo($objError, 500);
    }
};

ValidFormValidator.prototype.showPage = function (strAlert) {
    strAlert = (typeof this.pageAlert !== "undefined") ? this.pageAlert : strAlert;

    if (typeof strAlert !== "undefined" && strAlert !== "") {
        var $objPageError = jQuery("#" + this.id).find(".vf__page:visible").find(".vf__page_error");

        if ($objPageError.length > 0) {
            this.removePage();
        }

        jQuery("#" + this.id).find(".vf__page:visible").prepend("<div class=\"vf__page_error\"><p>" + strAlert + "</p></div>");
    }

    //*** Jump to the first error.
    var $objError = jQuery("div.vf__error:first");
    if ($objError.length > 0) {
        jQuery.scrollTo($objError, 500);
    }
};

ValidFormValidator.prototype.removePage = function() {
    jQuery("#" + this.id + " .vf__page:visible div.vf__page_error").remove();
};

/**
 * ValidFormValidator Class
 * Display class used to push alerts regarding form validation
 * to the browser.
 *
 * @param {String} strElementId   ID of the form element to validate
 * @param {String} strElementName Name of the form element to validate
 */
function ValidFormFieldValidator(strElementId, strElementName) {
    /**
     * Form element ID
     * @type {String}
     */
    this.id                 = strElementId;
    /**
     * Form element name
     * @type {String}
     */
    this.name               = strElementName;
    /**
     * Element's disabled status
     * @type {Boolean}
     */
    this.disabled             = ($("#" + strElementId).attr("disabled") === "disabled");

    this.defaultValue = $("#" + strElementId).val();

    this.check              = null;
    /**
     * Type error message
     * @type {String}
     */
    this.typeError          = "";
    /**
     * Required status
     * @type {Boolean}
     */
    this.required           = false;
    /**
     * Required error message
     * @type {String}
     */
    this.requiredError      = "";
    /**
     * Hint message
     * @type {String}
     */
    this.hint               = null;
    /**
     * Hint error message
     * @type {String}
     */
    this.hintError          = "";
    /**
     * Minimum input length
     * @type {Integer}
     */
    this.minLength          = null;
    /**
     * Minimum input length error
     * @type {String}
     */
    this.minLengthError     = "";
    /**
     * Maximum input length
     * @type {Integer}
     */
    this.maxLength          = null;
    /**
     * Maximum input length error
     * @type {String}
     */
    this.maxLengthError     = "";
}

/**
 * Element validator
 * @param  {mixed} value Value of the element
 * @return {boolean}       True if value is valid, false if not.
 */
ValidFormFieldValidator.prototype.validate = function(value) {
    var objElement = jQuery("#" + this.id);
    var value = objElement.val();

    this.removeAlert();

    // Check if the disabled attribute has been set if so, no validation is
    // needed because this field value will not be submitted anyway.
    this.disabled = (objElement.attr("disabled") === "disabled");


    if (!this.disabled) {
        try {
            var objDOMElement = objElement.get(0);
            /*** Redirect to error handler if a checkbox or radio is found.
                    This is done for cross-browser functionality. */

            switch (objDOMElement.type) {
                case 'checkbox':
                case 'radiobutton':
                    throw new Error("Checkbox or radio button detected.");
            }

            //*** Required, but empty is not good.
            if (this.required && value === "") {
                this.showAlert(this.requiredError);
                return false;
            }

            //*** Check if there is a matchWith field to validate against
            if (typeof this.matchWith === "object") {
                if (this.matchWith.validate()) {
                    if (jQuery("#" + this.matchWith.id).val() != value) {
                        this.matchWith.validator.showAlert(this.matchError);
                        this.showAlert(this.matchError);
                        return false;
                    }
                }
            }

            //*** Value is the same as hint value.
            if (this.hint && value == this.hint && this.required) {
                this.showAlert(this.hintError);
                return false;
            }

            //*** Check if the length of the value is within the range.
            if (this.minLength > 0 && value.length < this.minLength) {
                if (this.required || (!this.required && value !== "")) {
                    this.showAlert(sprintf(this.minLengthError, this.minLength));
                    return false;
                }
            }

            if (this.maxLength > 0 && value.length > this.maxLength) {
                this.showAlert(sprintf(this.maxLengthError, this.maxLength));
                return false;
            }

            //*** Check specific types using regular expression.
            if(typeof this.check != "function" && typeof this.check != "object") {
                return true;
            } else {
                if (this.required && value !== "") {
                    blnReturn = this.check.test(value);

                    if (blnReturn === false) {
                        this.showAlert(this.typeError);
                    }

                    return blnReturn;
                } else {
                    return true;
                }
            }
        } catch(err) {
            var objElements = jQuery("input[name='" + this.name + "']");
            if (objElements.length > 0) {
                var objValidElements = objElements.filter(":checked");
                value = objValidElements.val();

                //*** Required, but empty is not good.
                if (this.required && typeof value === "undefined" && objElements.attr("disabled") !== "disabled") {
                    this.showAlert(this.requiredError);
                    return false;
                } else if (!this.required && typeof value === "undefined") {
                    return true;
                } else if (this.required && typeof value === "undefined" && objElements.attr("disabled") == "disabled") {
                    return true;
                }

                //*** Check if the length of the value is within the range.
                if (this.minLength > 0 && objValidElements.length < this.minLength) {
                    this.showAlert(sprintf(this.minLengthError, this.minLength));
                    return false;
                }

                if (this.maxLength > 0 && objValidElements.length > this.maxLength) {
                    this.showAlert(sprintf(this.maxLengthError, this.maxLength));
                    return false;
                }

                //*** Check specific types using the type array.
                // FIXME: This block throws errors on checkboxes in a list field.
				/*
                if (typeof this.check === "object") {
                    for (var intCount = 0; intCount < objValidElements.length; intCount++) {
                        if (!ValidForm.inArray(this.check, objValidElements.get(intCount))) {
                            this.showAlert(this.typeError);
                            return false;
                        }
                    }
                }
				*/

                return true;
            } else {
                return true;
            }
        }
    } else {
        return true;
    }

};

ValidFormFieldValidator.prototype.removeAlert = function() {
    var objElement = jQuery("#" + this.id);

    if (objElement.length === 0) {
        objElement = jQuery("input[name='" + this.name + "']:first").closest(".vf__list");
    }

    objElement.closest(".vf__error").removeClass("vf__error").find("p.vf__error").remove();

    if (objElement.closest("div").hasClass("vf__multifielditem")) {
        objElement.closest(".vf__multifield").removeClass("vf__error").find("p.vf__error").remove();
    }
};

ValidFormFieldValidator.prototype.showAlert = function(strAlert) {
    var objElement = jQuery("#" + this.id);
    if (objElement.length === 0) {
        objElement = jQuery("input[name='" + this.name + "']:first").closest(".vf__list");
    }

    var objMultifieldItem = objElement.closest("div");
    if (objMultifieldItem.hasClass("vf__multifielditem")) {
        objMultifieldItem.addClass("vf__error");

        var objAlertWrap = objMultifieldItem.closest(".vf__multifield");

        objAlertWrap.addClass("vf__error");
        if (objAlertWrap.find("p.vf__error").length <= 0) {
            // Only add an error message if we haven't done so before.
            objAlertWrap.prepend("<p class=\"vf__error\">" + strAlert + "</p>");
        }
    } else {
        objElement.closest("div.vf__optional, div.vf__required").addClass("vf__error").prepend("<p class=\"vf__error\">" + strAlert + "</p>");
    }

    $("#" + this.id).trigger("VF_ShowAlert", [{FormFieldValidator: this, errorMsg: strAlert}]);
};

/**
 * Copyright (c) 2007-2012 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * @author Ariel Flesler
 * @version 1.4.3.1
 */
;(function($){var h=$.scrollTo=function(a,b,c){$(window).scrollTo(a,b,c);};h.defaults={axis:'xy',duration:parseFloat($.fn.jquery)>=1.3?0:1,limit:true};h.window=function(a){return $(window)._scrollable();};$.fn._scrollable=function(){return this.map(function(){var a=this,isWin=!a.nodeName||$.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!isWin)return a;var b=(a.contentWindow||a).document||a.ownerDocument||a;return/webkit/i.test(navigator.userAgent)||b.compatMode=='BackCompat'?b.body:b.documentElement;});};$.fn.scrollTo=function(e,f,g){if(typeof f=='object'){g=f;f=0;}if(typeof g=='function')g={onAfter:g};if(e=='max')e=9e9;g=$.extend({},h.defaults,g);f=f||g.duration;g.queue=g.queue&&g.axis.length>1;if(g.queue)f/=2;g.offset=both(g.offset);g.over=both(g.over);return this._scrollable().each(function(){if(e===null)return;var d=this,$elem=$(d),targ=e,toff,attr={},win=$elem.is('html,body');switch(typeof targ){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ)){targ=both(targ);break;}targ=$(targ,this);if(!targ.length)return;break;case'object':if(targ.is||targ.style)toff=(targ=$(targ)).offset();}$.each(g.axis.split(''),function(i,a){var b=a=='x'?'Left':'Top',pos=b.toLowerCase(),key='scroll'+b,old=d[key],max=h.max(d,a);if(toff){attr[key]=toff[pos]+(win?0:old-$elem.offset()[pos]);if(g.margin){attr[key]-=parseInt(targ.css('margin'+b))||0;attr[key]-=parseInt(targ.css('border'+b+'Width'))||0;}attr[key]+=g.offset[pos]||0;if(g.over[pos])attr[key]+=targ[a=='x'?'width':'height']()*g.over[pos];}else{var c=targ[pos];attr[key]=c.slice&&c.slice(-1)=='%'?parseFloat(c)/100*max:c;}if(g.limit&&/^\d+$/.test(attr[key]))attr[key]=attr[key]<=0?0:Math.min(attr[key],max);if(!i&&g.queue){if(old!=attr[key])animate(g.onAfterFirst);delete attr[key];}});animate(g.onAfter);function animate(a){$elem.animate(attr,f,g.easing,a&&function(){a.call(this,e,g);});}}).end();};h.max=function(a,b){var c=b=='x'?'Width':'Height',scroll='scroll'+c;if(!$(a).is('html,body'))return a[scroll]-$(a)[c.toLowerCase()]();var d='client'+c,html=a.ownerDocument.documentElement,body=a.ownerDocument.body;return Math.max(html[scroll],body[scroll])-Math.min(html[d],body[d]);};function both(a){return typeof a=='object'?a:{top:a,left:a};}})(jQuery);

/**
 * sprintf() for JavaScript 0.7-beta1
 * http://www.diveintojavascript.com/projects/javascript-sprintf
 * Copyright (c) Alexandru Marasteanu <alexaholic [at) gmail (dot] com>
 * All rights reserved.
 */
var sprintf=function(){function a(a){return Object.prototype.toString.call(a).slice(8,-1).toLowerCase()}function b(a,b){for(var c=[];b>0;c[--b]=a){}return c.join("")}var c=function(){if(!c.cache.hasOwnProperty(arguments[0])){c.cache[arguments[0]]=c.parse(arguments[0])}return c.format.call(null,c.cache[arguments[0]],arguments)};c.format=function(c,d){var e=1,f=c.length,g="",h,i=[],j,k,l,m,n,o;for(j=0;j<f;j++){g=a(c[j]);if(g==="string"){i.push(c[j])}else if(g==="array"){l=c[j];if(l[2]){h=d[e];for(k=0;k<l[2].length;k++){if(!h.hasOwnProperty(l[2][k])){throw sprintf('[sprintf] property "%s" does not exist',l[2][k])}h=h[l[2][k]]}}else if(l[1]){h=d[l[1]]}else{h=d[e++]}if(/[^s]/.test(l[8])&&a(h)!="number"){throw sprintf("[sprintf] expecting number but found %s",a(h))}switch(l[8]){case"b":h=h.toString(2);break;case"c":h=String.fromCharCode(h);break;case"d":h=parseInt(h,10);break;case"e":h=l[7]?h.toExponential(l[7]):h.toExponential();break;case"f":h=l[7]?parseFloat(h).toFixed(l[7]):parseFloat(h);break;case"o":h=h.toString(8);break;case"s":h=(h=String(h))&&l[7]?h.substring(0,l[7]):h;break;case"u":h=Math.abs(h);break;case"x":h=h.toString(16);break;case"X":h=h.toString(16).toUpperCase();break}h=/[def]/.test(l[8])&&l[3]&&h>=0?"+"+h:h;n=l[4]?l[4]=="0"?"0":l[4].charAt(1):" ";o=l[6]-String(h).length;m=l[6]?b(n,o):"";i.push(l[5]?h+m:m+h)}}return i.join("")};c.cache={};c.parse=function(a){var b=a,c=[],d=[],e=0;while(b){if((c=/^[^\x25]+/.exec(b))!==null){d.push(c[0])}else if((c=/^\x25{2}/.exec(b))!==null){d.push("%")}else if((c=/^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(b))!==null){if(c[2]){e|=1;var f=[],g=c[2],h=[];if((h=/^([a-z_][a-z_\d]*)/i.exec(g))!==null){f.push(h[1]);while((g=g.substring(h[0].length))!==""){if((h=/^\.([a-z_][a-z_\d]*)/i.exec(g))!==null){f.push(h[1])}else if((h=/^\[(\d+)\]/.exec(g))!==null){f.push(h[1])}else{throw"[sprintf] huh?"}}}else{throw"[sprintf] huh?"}c[2]=f}else{e|=2}if(e===3){throw"[sprintf] mixing positional and named placeholders is not (yet) supported"}d.push(c)}else{throw"[sprintf] huh?"}b=b.substring(c[0].length)}return d};return c}();var vsprintf=function(a,b){b.unshift(a);return sprintf.apply(null,b)};