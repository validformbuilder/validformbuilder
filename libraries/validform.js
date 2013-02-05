/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2.
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/

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
	this.id 				= strFormId;

	/**
	 * Main alert
	 * @type {String}
	 */
	this.mainAlert			= "";
}

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
	this.id 				= strElementId;
	/**
	 * Form element name
	 * @type {String}
	 */
	this.name 				= strElementName;
	/**
	 * Element's disabled status
	 * @type {Boolean}
	 */
	this.disabled 			= !!($("#" + strElementId).attr("disabled") === "disabled");

	this.check				= null;
	/**
	 * Type error message
	 * @type {String}
	 */
	this.typeError			= "";
	/**
	 * Required status
	 * @type {Boolean}
	 */
	this.required			= false;
	/**
	 * Required error message
	 * @type {String}
	 */
	this.requiredError		= "";
	/**
	 * Hint message
	 * @type {String}
	 */
	this.hint				= null;
	/**
	 * Hint error message
	 * @type {String}
	 */
	this.hintError			= "";
	/**
	 * Minimum input length
	 * @type {Integer}
	 */
	this.minLength			= null;
	/**
	 * Minimum input length error
	 * @type {String}
	 */
	this.minLengthError		= "";
	/**
	 * Maximum input length
	 * @type {Integer}
	 */
	this.maxLength			= null;
	/**
	 * Maximum input length error
	 * @type {String}
	 */
	this.maxLengthError		= "";
}

/**
 * ValidFormElement Class
 * Holds an element that can be validated
 * @param {String} strFormId      Form ID
 * @param {String} strElementName Form element name
 * @param {String} strElementId   Form element ID
 * @param {String} strValidation  Validation regular expression
 */
function ValidFormElement(strFormId, strElementName, strElementId, strValidation) {
	this.formId					= strFormId;
	this.id 					= strElementId;
	this.name 					= strElementName;
	this.disabled 				= !!($("#" + strElementId).attr("disabled") === "disabled");
	this.validator 				= new ValidFormFieldValidator(strElementId, strElementName);
	this.validator.check 		= strValidation;
	this.validator.required		= false;
	this.validator.minLength	= null;
	this.validator.maxLength	= null;

	if (ValidFormElement.arguments.length > 4) {
		this.validator.required = ValidFormElement.arguments[4];
	}

	if (ValidFormElement.arguments.length > 5) {
		this.validator.maxLength = ValidFormElement.arguments[5];
	}

	if (ValidFormElement.arguments.length > 6) {
		this.validator.minLength = ValidFormElement.arguments[6];
	}

	if (ValidFormElement.arguments.length > 7) {
		this.validator.hint = ValidFormElement.arguments[7];

		var __this = this;
		if (this.validator.hint != "") {
			jQuery("#" + this.id)
				.bind("focus", function(){
					if (jQuery(this).val() == __this.validator.hint) {
						jQuery(this).val("");
						jQuery(this).parent().removeClass("vf__hint");
					}
				})
				.bind("blur", function(){
					if (jQuery(this).val() == "" && __this.validator.required) {
						jQuery(this).val(__this.validator.hint);
						jQuery(this).parent().addClass("vf__hint");
					}
				});
		}
	}

	if (ValidFormElement.arguments.length > 8) {
		this.validator.typeError = ValidFormElement.arguments[8];
	}

	if (ValidFormElement.arguments.length > 9) {
		this.validator.requiredError = ValidFormElement.arguments[9];
	}

	if (ValidFormElement.arguments.length > 10) {
		this.validator.hintError = ValidFormElement.arguments[10];
	}

	if (ValidFormElement.arguments.length > 11) {
		this.validator.minLengthError = ValidFormElement.arguments[11];
	}

	if (ValidFormElement.arguments.length > 12) {
		this.validator.maxLengthError = ValidFormElement.arguments[12];
	}
}

/**
 * ValidForm class
 * @param {String} strFormId            The form ID
 * @param {String} strMainAlert         The main form alert
 * @param {Boolean} blnAllowPreviousPage If true, users can click 'previous' in wizards. If false, this is disabled.
 */
function ValidForm(strFormId, strMainAlert, blnAllowPreviousPage) {
	this.id = strFormId;
	this.elements = {};
	this.pages = [];
	this.valid = false;
	this.validator = new ValidFormValidator(this.id);
	this.validator.mainAlert = strMainAlert;
	this.events = [];
	this.cachedEvents = [];
	this.customEvents = [
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
	this.labels = {};
	this.allowPreviousPage = (typeof blnAllowPreviousPage !== "undefined") ? blnAllowPreviousPage : true;
	this.__continueExecution = true;

	// Initialize ValidForm class
	this.init();
}

/**
 * Initialize ValidForm Builder client side after all elements are added to the collection.
 */
ValidForm.prototype.init = function() {
	var __this = this;

	// Handle disabled elements and make sure all sub-elements are disabled as well.
	this.traverseDisabledElements();

	// This is where the magic happens: onSubmit; validate form.
	jQuery("#" + __this.id).bind("submit", function(){
		jQuery("#" + this.id).trigger("VF_BeforeSubmit", [{ValidForm: __this}]);
		if (typeof __this.events.beforeSubmit == "function") {
			__this.events.beforeSubmit(__this);
		}

		if (__this.__continueExecution) {
			if (__this.pages.length > 1) {
				// Validation has been done on each page individually.
				// No need to re-validate here.
				return true;
			} else {
				return __this.validate();
			}
		} else {
			return false;
		}
	});

	// Dynamic duplication logic.
	this.dynamicDuplication();
};

ValidForm.prototype.initialize = function () {
	// Placeholder method for deferred initialization
}

ValidForm.prototype.addCondition = function (objComparison) {

}

/**
 * Parse field errors from javascript object as such:
 *
 * [
 * 		{fieldName: "Error message here"},
 * 		{fieldName: "Error message here"},
 * 		.... etc.
 * ]
 *
 * This enables us to push validation errors from ajax return objects.
 *
 * @param  {object} objFields The fields object which contains fieldname-error pairs
 */
ValidForm.prototype.showAlerts = function (objFields) {
	var __this = this;
	try {
		if ($(objFields).length > 0) {
			$(objFields).each(function () {
				var objFieldError = this;

				for (var fieldName in objFieldError) {
					if (objFieldError.hasOwnProperty(fieldName)) {
						var objField = __this.getElement(fieldName);

						if (objField !== null) {
							// Field found in current form
							var objValidator = objField.validator;

							objValidator.removeAlert();
							objValidator.showAlert(objFieldError[fieldName]);
						}
					}
				}
			});

			$("#" + __this.id).trigger("VF_ShowAlerts", [{ValidForm: __this, invalidFields: objFields}]);
		}
	} catch (e) {
		try {
			console.error("Show alerts failed: ", e.message, e); // Log error
		} catch (e) {} // Or die trying
	}
}

ValidForm.prototype.setLabel = function (key, value) {
	if (typeof value !== "undefined") {
		this.labels[key] = value;
	} else {
		throw new Error("Cannot set empty label in ValidForm.setLabel('" + key + "', '" + value + "')");
	}
}

ValidForm.prototype.showFirstError = function () {
	var __this = this;

	if (jQuery("#" + __this.id).has(".vf__error").length > 0) {
		var $error 	= jQuery(".vf__error:first");
		var $page 	= $error.parentsUntil(".vf__page").parent();

		__this.currentPage.hide();
		__this.showPage($page);
	}
}

ValidForm.prototype.matchfields = function (strSecondFieldId, strFirstFieldId, strMatchError) {
	var objElement = this.getElement(jQuery("#" + strSecondFieldId).attr("name"));
	objElement.validator.matchWith = this.getElement(jQuery("#" + strFirstFieldId).attr("name"));
	objElement.validator.matchError = strMatchError;
}

ValidForm.prototype.traverseDisabledElements = function () {
	var __this = this;

	jQuery("#" + this.id + " fieldset.vf__disabled").each(function(){
		var fieldset = this;

		jQuery("input, select, textarea", fieldset).attr("disabled", "disabled");
		jQuery(".vf__dynamic a", fieldset).addClass("vf__disabled");
		jQuery("legend input", fieldset)
			.removeAttr("disabled");

		__this.attachAreaEvents(jQuery("legend input", fieldset));
	});
}

ValidForm.prototype.dynamicDuplication = function () {
	var __this 	= this;


	// Bind click event to duplicate button
	jQuery(".vf__dynamic a").bind("click", function() {
		var $anchor = jQuery(this);

		//*** Call custom event if set.
		jQuery("#" + this.id).trigger("VF_BeforeDynamicChange", [{ValidForm: __this, objAnchor: $anchor}]);
		if (typeof __this.events.beforeDynamicChange == "function") {
			__this.events.beforeDynamicChange(__this, $anchor);
		}

		if (!jQuery(this).parent().prev().hasClass("vf__disabled")) {
			//*** Update dynamic field counter.
			var $original 	= $anchor.parent().prev();
			var copy 		= $original.clone();

			//*** Clear values.
			var names = jQuery(this).data("target-name").split("|");
			var ids = jQuery(this).data("target-id").split("|");

			copy.find("input[name$='_dynamic']").remove();

			jQuery.each(names, function(index, fieldname){
				//*** Fix every field in an area or multifield.
				var counter = $("#" + fieldname + "_dynamic");

				// Set value to number '0' if value is NaN
				if (isNaN(parseInt(counter.val()))) {
					counter.val(0);
				}

				counter.val(parseInt(counter.val()) + 1);
				var search 	= (parseInt(counter.val()) == 1) ? fieldname : fieldname + "_" + (parseInt(counter.val()) - 1);

				copy.find("[name='" + search + "']").each(function(){
					if (jQuery(this).attr("type") == "radio" ||
							jQuery(this).attr("type") == "checkbox") {
						//*** Radio buttons and checkboxes have to be treated differently.
						var fieldId;
						if (counter.val() == 1) {
							fieldId = jQuery(this).attr("id");
						} else {
							var arrFieldId = jQuery(this).attr("id").split("_");
							arrFieldId.pop();
							fieldId = arrFieldId.join("_");
						}

						jQuery(this)
							.removeAttr("checked")
							.attr("name", fieldname + "_" + counter.val())
							.attr("id", fieldId + "_" + counter.val())
							.parent("label").attr("for", fieldId + "_" + counter.val());
					} else {
						//*** Normal fields (input, textarea) are easy.
						jQuery(this)
							.attr("value", "")
							.attr("name", fieldname + "_" + counter.val())
							.attr("id", ids[index] + "_" + counter.val())
							.prev("label").attr("for", ids[index] + "_" + counter.val());
					}
				});

				//*** Add fields to the form.
				var objOriginal = __this.getElement(fieldname);
				var objCopy = jQuery.extend(new ValidFormElement(), objOriginal);
				objCopy.id = ids[index] + "_" + counter.val();
				objCopy.name = fieldname + "_" + counter.val();
				objCopy.validator = jQuery.extend(new ValidFormFieldValidator(), objOriginal.validator);
				objCopy.validator.id = objCopy.id;
				objCopy.validator.required = false;
				__this.addElement(objCopy);
			});

			//*** Remove 'required' styling.
			copy
				.find(".vf__required")
				.removeClass("vf__required")
				.addClass("vf__optional")
			copy
				.removeClass("vf__required")
				.removeClass("vf__error")
				.addClass("vf__optional");

			copy.find("p.vf__error").remove();
			copy.find(".vf__error").removeClass("vf__error");

			jQuery(this).parent().before(copy);

			//*** Fix click event on active areas.
			if (copy.hasClass("vf__area")) {
				var copiedTrigger = jQuery("legend :checkbox", copy);
				var originalTrigger = jQuery("legend :checkbox", $original);

				if (copiedTrigger.length > 0) {
					var counter = $("#" + copiedTrigger.attr("name") + "_dynamic");

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

			//*** Call custom event if set.
			jQuery("#" + __this.id).trigger("VF_AfterDynamicChange", [{ValidForm: __this, objAnchor: $anchor, objCopy: copy}]);
			if (typeof __this.events.afterDynamicChange == "function") {
				__this.events.afterDynamicChange({ValidForm: __this, objAnchor: $anchor, objCopy: copy});
			}
		}

		return false;
	});
};

ValidForm.prototype.attachAreaEvents = function(objActiveTrigger) {
	var __this = this;

	objActiveTrigger.unbind("click").bind("click", function(){
		var fieldset = jQuery(objActiveTrigger).parentsUntil(".vf__area").parent(".vf__area");

		if (this.checked) {
			// Enable active area
			jQuery("input, select, textarea", fieldset).removeAttr("disabled");
			jQuery(".vf__dynamic a", fieldset).removeClass("vf__disabled");
			jQuery(fieldset).removeClass("vf__disabled");

			var $dynamicTrigger = jQuery(fieldset).data("vf__dynamicTrigger");
			if (typeof $dynamicTrigger === "object") {
				$dynamicTrigger.show();
			}

			$("#" + __this.id).trigger("VF_EnableActiveArea", [{ValidForm: __this, objArea: fieldset}]);
		} else {
			// Disable active area & remove error's

			var inputNames = [];
			jQuery("div > input, select, textarea", fieldset)
				.attr("disabled", "disabled")
				.each(function () {
					inputNames.push($(this).attr("name"));
				});

			jQuery(".vf__dynamic a", fieldset).addClass("vf__disabled");
			jQuery("legend input", fieldset).removeAttr("disabled");
			jQuery(fieldset).addClass("vf__disabled");

			// Get the dynamic trigger, if available
			var $dynamicTrigger = $("[data-target-id='" + inputNames.join("|") + "']");
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
	}).click();
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
	if (arguments.length > 0 && typeof(arguments[0]) == "object") {
		this.elements[arguments[0].name] = arguments[0];

		return true;
	} else {
		var typeError		= "";
		var required		= false;
		var requiredError	= "";
		var hint			= null;
		var hintError		= "";
		var minLength		= null;
		var minLengthError	= "";
		var maxLength		= null;
		var maxLengthError	= "";

		if (arguments.length > 0) {
			var strElementId = arguments[0];
		} else {
			return false;
		}

		if (arguments.length > 1) {
			var strElementName = arguments[1];
		} else {
			return false;
		}

		if (arguments.length > 2) {
			var strValidation = arguments[2];
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

		this.elements[strElementName] = new ValidFormElement(this.id, strElementName, strElementId, strValidation, required, maxLength, minLength, hint, typeError, requiredError, hintError, minLengthError, maxLengthError);
	}
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
		objElement.reset();
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

			if (((strSelector !== null) && (jQuery(strSelector).has(jQuery("[name='" + objElement.name + "']")).length > 0))
				|| (strSelector == null)) {
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

ValidFormElement.prototype.validate = function() {
	return this.validator.validate();
};

ValidFormElement.prototype.reset = function() {
	this.validator.removeAlert();

	var objElement = jQuery("#" + this.id);
	objElement.val("");
};

ValidFormValidator.prototype.removeMain = function() {
	jQuery("#" + this.id + " div.vf__main_error").remove();
};

ValidFormValidator.prototype.showMain = function() {
	if (this.mainAlert != "") {
		jQuery("#" + this.id).prepend("<div class=\"vf__main_error\"><p>" + this.mainAlert + "</p></div>");
	}

	//*** Jump to the first error.
	jQuery.scrollTo(jQuery("div.vf__error:first"), 500);
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
	jQuery.scrollTo(jQuery("div.vf__error:first"), 500);
}

ValidFormValidator.prototype.removePage = function() {
	jQuery("#" + this.id + " .vf__page:visible div.vf__page_error").remove();
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
	this.disabled = !!(objElement.attr("disabled") === "disabled");


	if (!this.disabled) {
		try {
			var objDOMElement = objElement.get(0);
			/*** Redirect to error handler if a checkbox or radio is found.
					This is done for cross-browser functionality. */

			switch (objDOMElement.type) {
				case 'checkbox':
				case 'radiobutton':
					throw "Checkbox or radio button detected.";
					break;
			}

			//*** Required, but empty is not good.
			if (this.required && value == "") {
				this.showAlert(this.requiredError);
				return false;
			}

			//*** Check if there is a matchWith field to validate against
			if (typeof this.matchWith == "object") {
				if (this.matchWith.validate()) {
					if (jQuery("#" + this.matchWith.id).val() != value) {
						this.matchWith.validator.showAlert(this.matchError);
						this.showAlert(this.matchError);
						return false;
					}
				}
			}

			//*** Value is the same as hint value.
			if (this.hint && value == this.hint) {
				this.showAlert(this.hintError);
				return false;
			}

			//*** Check if the length of the value is within the range.
			if (this.minLength > 0 && value.length < this.minLength) {
				this.showAlert(sprintf(this.minLengthError, this.minLength));
				return false;
			}

			if (this.maxLength > 0 && value.length > this.maxLength) {
				this.showAlert(sprintf(this.maxLengthError, this.maxLength));
				return false;
			}

			//*** Check specific types using regular expression.
			if(typeof this.check != "function" && typeof this.check != "object") {
				return true;
			} else {
				blnReturn = this.check.test(value);
				if (blnReturn == false) this.showAlert(this.typeError);
				return blnReturn;
			}
		} catch(e) {
			var objElements = jQuery("input[name='" + this.name + "']");
			if (objElements.length > 0) {
				var objValidElements = objElements.filter(":checked");
				value = objValidElements.val();

				//*** Required, but empty is not good.
				if (this.required && value == undefined && objElements.attr("disabled") !== "disabled") {
					this.showAlert(this.requiredError);
					return false;
				} else if (!this.required && value == undefined) {
					return true;
				} else if (this.required && value == undefined && objElements.attr("disabled") == "disabled") {
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
				if (typeof this.check == "array") {
					for (var intCount = 0; intCount < objValidElements.length; intCount++) {
						if (!ValidForm.inArray(this.check, objValidElements.get(intCount))) {
							this.showAlert(this.typeError);
							return false;
						}
					}
				}

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

	if (objElement.length == 0) {
		objElement = jQuery("input[name='" + this.name + "']:first").parent().parent();
	}

	if (objElement.parent("div").hasClass("vf__multifielditem")) {
		objElement.parent("div").removeClass("vf__error");
		if (objElement.parent("div").parent("div").find(".vf__error").length < 2) {
			objElement.parent("div").parent("div").removeClass("vf__error").find("p.vf__error").remove();
		}
	} else {
		objElement.parent("div").removeClass("vf__error").find("p.vf__error").remove();
	}
};

ValidFormFieldValidator.prototype.showAlert = function(strAlert) {
	var objElement = jQuery("#" + this.id);
	if (objElement.length == 0) {
		objElement = jQuery("input[name='" + this.name + "']:first").parent().parent();
	}

	if (objElement.parent("div").hasClass("vf__multifielditem")) {
		objElement.parent("div").addClass("vf__error");
		if (!objElement.parent("div").parent("div").hasClass("vf__error")) {
			objElement.parent("div").parent("div").addClass("vf__error").prepend("<p class=\"vf__error\">" + strAlert + "</p>");
		}
	} else {
		objElement.parent("div").addClass("vf__error").prepend("<p class=\"vf__error\">" + strAlert + "</p>");
	}
};

/**
 * Copyright (c) 2007-2012 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * @author Ariel Flesler
 * @version 1.4.3.1
 */
;(function($){var h=$.scrollTo=function(a,b,c){$(window).scrollTo(a,b,c)};h.defaults={axis:'xy',duration:parseFloat($.fn.jquery)>=1.3?0:1,limit:true};h.window=function(a){return $(window)._scrollable()};$.fn._scrollable=function(){return this.map(function(){var a=this,isWin=!a.nodeName||$.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!isWin)return a;var b=(a.contentWindow||a).document||a.ownerDocument||a;return/webkit/i.test(navigator.userAgent)||b.compatMode=='BackCompat'?b.body:b.documentElement})};$.fn.scrollTo=function(e,f,g){if(typeof f=='object'){g=f;f=0}if(typeof g=='function')g={onAfter:g};if(e=='max')e=9e9;g=$.extend({},h.defaults,g);f=f||g.duration;g.queue=g.queue&&g.axis.length>1;if(g.queue)f/=2;g.offset=both(g.offset);g.over=both(g.over);return this._scrollable().each(function(){if(e==null)return;var d=this,$elem=$(d),targ=e,toff,attr={},win=$elem.is('html,body');switch(typeof targ){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ)){targ=both(targ);break}targ=$(targ,this);if(!targ.length)return;case'object':if(targ.is||targ.style)toff=(targ=$(targ)).offset()}$.each(g.axis.split(''),function(i,a){var b=a=='x'?'Left':'Top',pos=b.toLowerCase(),key='scroll'+b,old=d[key],max=h.max(d,a);if(toff){attr[key]=toff[pos]+(win?0:old-$elem.offset()[pos]);if(g.margin){attr[key]-=parseInt(targ.css('margin'+b))||0;attr[key]-=parseInt(targ.css('border'+b+'Width'))||0}attr[key]+=g.offset[pos]||0;if(g.over[pos])attr[key]+=targ[a=='x'?'width':'height']()*g.over[pos]}else{var c=targ[pos];attr[key]=c.slice&&c.slice(-1)=='%'?parseFloat(c)/100*max:c}if(g.limit&&/^\d+$/.test(attr[key]))attr[key]=attr[key]<=0?0:Math.min(attr[key],max);if(!i&&g.queue){if(old!=attr[key])animate(g.onAfterFirst);delete attr[key]}});animate(g.onAfter);function animate(a){$elem.animate(attr,f,g.easing,a&&function(){a.call(this,e,g)})}}).end()};h.max=function(a,b){var c=b=='x'?'Width':'Height',scroll='scroll'+c;if(!$(a).is('html,body'))return a[scroll]-$(a)[c.toLowerCase()]();var d='client'+c,html=a.ownerDocument.documentElement,body=a.ownerDocument.body;return Math.max(html[scroll],body[scroll])-Math.min(html[d],body[d])};function both(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);

/**
 * sprintf() for JavaScript 0.7-beta1
 * http://www.diveintojavascript.com/projects/javascript-sprintf
 * Copyright (c) Alexandru Marasteanu <alexaholic [at) gmail (dot] com>
 * All rights reserved.
 */
var sprintf=function(){function a(a){return Object.prototype.toString.call(a).slice(8,-1).toLowerCase()}function b(a,b){for(var c=[];b>0;c[--b]=a){}return c.join("")}var c=function(){if(!c.cache.hasOwnProperty(arguments[0])){c.cache[arguments[0]]=c.parse(arguments[0])}return c.format.call(null,c.cache[arguments[0]],arguments)};c.format=function(c,d){var e=1,f=c.length,g="",h,i=[],j,k,l,m,n,o;for(j=0;j<f;j++){g=a(c[j]);if(g==="string"){i.push(c[j])}else if(g==="array"){l=c[j];if(l[2]){h=d[e];for(k=0;k<l[2].length;k++){if(!h.hasOwnProperty(l[2][k])){throw sprintf('[sprintf] property "%s" does not exist',l[2][k])}h=h[l[2][k]]}}else if(l[1]){h=d[l[1]]}else{h=d[e++]}if(/[^s]/.test(l[8])&&a(h)!="number"){throw sprintf("[sprintf] expecting number but found %s",a(h))}switch(l[8]){case"b":h=h.toString(2);break;case"c":h=String.fromCharCode(h);break;case"d":h=parseInt(h,10);break;case"e":h=l[7]?h.toExponential(l[7]):h.toExponential();break;case"f":h=l[7]?parseFloat(h).toFixed(l[7]):parseFloat(h);break;case"o":h=h.toString(8);break;case"s":h=(h=String(h))&&l[7]?h.substring(0,l[7]):h;break;case"u":h=Math.abs(h);break;case"x":h=h.toString(16);break;case"X":h=h.toString(16).toUpperCase();break}h=/[def]/.test(l[8])&&l[3]&&h>=0?"+"+h:h;n=l[4]?l[4]=="0"?"0":l[4].charAt(1):" ";o=l[6]-String(h).length;m=l[6]?b(n,o):"";i.push(l[5]?h+m:m+h)}}return i.join("")};c.cache={};c.parse=function(a){var b=a,c=[],d=[],e=0;while(b){if((c=/^[^\x25]+/.exec(b))!==null){d.push(c[0])}else if((c=/^\x25{2}/.exec(b))!==null){d.push("%")}else if((c=/^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(b))!==null){if(c[2]){e|=1;var f=[],g=c[2],h=[];if((h=/^([a-z_][a-z_\d]*)/i.exec(g))!==null){f.push(h[1]);while((g=g.substring(h[0].length))!==""){if((h=/^\.([a-z_][a-z_\d]*)/i.exec(g))!==null){f.push(h[1])}else if((h=/^\[(\d+)\]/.exec(g))!==null){f.push(h[1])}else{throw"[sprintf] huh?"}}}else{throw"[sprintf] huh?"}c[2]=f}else{e|=2}if(e===3){throw"[sprintf] mixing positional and named placeholders is not (yet) supported"}d.push(c)}else{throw"[sprintf] huh?"}b=b.substring(c[0].length)}return d};return c}();var vsprintf=function(a,b){b.unshift(a);return sprintf.apply(null,b)};