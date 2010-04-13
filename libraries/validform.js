/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/

/**
 * ValidForm class
 *
 * @package ValidFormBuilder
 * @author Felix Langfeldt
 * @version 0.2.2
 */

function ValidFormValidator(strFormId) {
	/****************************/
	/* ValidFormValidator Class ********************************************/
	/* 
	/* Display class used to push alerts regarding form validation 
	/* to the browser.
	/*********************************************************************/
	
	this.id 				= strFormId;
	this.mainAlert			= "";
}

function ValidFormFieldValidator(strElementId) {
	/****************************/
	/* ValidFormValidator Class ********************************************/
	/* 
	/* Display class used to push alerts regarding form validation 
	/* to the browser.
	/*********************************************************************/
	
	this.id 				= strElementId;
	this.check				= null;
	this.typeError			= "";
	this.required			= false;
	this.requiredError		= "";
	this.hint				= null;
	this.hintError			= "";
	this.minLength			= null;
	this.minLengthError		= "";
	this.maxLength			= null;
	this.maxLengthError		= "";
}

function ValidFormElement(strFormId, strElementName, strElementId, strValidation) {
	/**************************/
	/* ValidFormElement Class ********************************************/
	/* 
	/* Holds an element that can be validated.
	/*********************************************************************/
	
	this.formId					= strFormId;
	this.id 					= strElementId;
	this.name 					= strElementName;
	this.validator 				= new ValidFormFieldValidator(strElementId);
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

function ValidForm(strFormId, strMainAlert) {
	this.id = strFormId;
	this.elements = new Object();
	this.valid = false;
	this.validator = new ValidFormValidator(this.id);
	this.validator.mainAlert = strMainAlert;
	this.init();
	this.events = [];
	this.customEvents = ["afterValidate"];
}

ValidForm.prototype.init = function() {
	var __this = this;

	jQuery("#" + this.id + " fieldset.vf__disabled").each(function(){
		var fieldset = this;
		
		jQuery("input, select, textarea", fieldset).attr("disabled", "disabled");
		jQuery("legend input", fieldset)
			.removeAttr("disabled")
			.bind("click", function(){
				if (this.checked) {
					jQuery("input, select, textarea", fieldset).removeAttr("disabled");
					jQuery(fieldset).removeClass("vf__disabled");
				} else {
					jQuery("input, select, textarea", fieldset).attr("disabled", "disabled");
					jQuery("legend input", fieldset).removeAttr("disabled");
					jQuery(fieldset).addClass("vf__disabled");
					
					//*** Remove errors.
					jQuery("div.vf__error", fieldset).each(function(){
						jQuery(this).removeClass("vf__error").find("p.vf__error").remove();
					});
				}
			});
	});
	
	jQuery("#" + this.id).bind("submit", function(){		
		return __this.validate();
	});
};



ValidForm.prototype.inArray = function(arrToSearch, value) {
	var i;
	for (i=0; i < arrToSearch.length; i++) {
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

ValidForm.prototype.addEvent = function(strEvent, callback){
	if (this.inArray(this.customEvents, strEvent)) {
		this.events[strEvent] = callback;
	} else {
		jQuery("#" + this.id).bind(strEvent, callback);
	}
}

ValidForm.prototype.validate = function() {
	/*************************/
	/* validate function     *********************************************/
	/* 
	/* Uses the ValidForms, ValidForm, ValidElement and ValidFormAlerter 
	/* objects to validate form elements.
	/*********************************************************************/
	
	this.valid = true;
	var arrMultiElements = new Array();
	var objDOMForm;
	
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
						
		for (var strElement in this.elements) {
			var objElement = this.elements[strElement];
			
			//*** Check if the element is part of an area.
			var objArea = jQuery("#" + objElement.id).parent().parent("fieldset.vf__area");
			if (objArea.length == 0) {
				//*** Group within an area.
				objArea = jQuery("input[name='" + objElement.id + "']").parent().parent().parent().parent("fieldset.vf__area");
			}
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
	} else {
		alert("An error occured while calling the Form.\nMessage: " + e.message);
		this.valid = false;
	}
	
	if (!this.valid) {
		this.validator.showMain();
	}
	
	if (typeof this.events["afterValidate"] == "function") {
		var callback = this.events["afterValidate"];
		callback();
	}
		
	return this.valid;
}

ValidFormElement.prototype.validate = function() {	
	return this.validator.validate();
};

ValidFormValidator.prototype.removeMain = function() {
	jQuery("#" + this.id + " div.vf__main_error").remove();
}

ValidFormValidator.prototype.showMain = function() {
	if (this.mainAlert != "") {
		jQuery("#" + this.id).prepend("<div class=\"vf__main_error\"><p>" + this.mainAlert + "</p></div>");
	}
	
	//*** Jump to the first error.
	jQuery.scrollTo(jQuery("div.vf__error:first"), 500);
}

ValidFormFieldValidator.prototype.validate = function(value) {
	var objElement = jQuery("#" + this.id);
	var value = objElement.val();
	
	this.removeAlert();
			
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
		} else if (!this.required && value == "") {
			return true;
		}

		//*** Value is the same as hint value.
		if (this.hint && value == this.hint) {
			this.showAlert(this.hintError);
			return false;
		}
		
		//*** Check if the length of the value is within the range.
		if (this.minLength > 0 && value.length < this.minLength) {
			this.showAlert(this.minLengthError);
			return false;
		}
		
		if (this.maxLength > 0 && value.length > this.maxLength) {
			this.showAlert(this.maxLengthError);
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
		var objElements = jQuery("input[name='" + this.id + "']");
		if (objElements.length > 0) {
			var objValidElements = objElements.filter(":checked");
			value = objValidElements.val();

			//*** Required, but empty is not good.
			if (this.required && value == undefined) {
				this.showAlert(this.requiredError);
				return false;
			} else if (!this.required && value == undefined) {
				return true;
			}

			//*** Check if the length of the value is within the range.
			if (this.minLength > 0 && objValidElements.length < this.minLength) {
				this.showAlert(this.minLengthError);
				return false;
			}
			
			if (this.maxLength > 0 && objValidElements.length > this.maxLength) {
				this.showAlert(this.maxLengthError);
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
}

ValidFormFieldValidator.prototype.removeAlert = function() {
	var objElement = jQuery("#" + this.id);
	if (objElement.length == 0) {
		objElement = jQuery("input[name='" + this.id + "']:first").parent().parent();
	}
	
	objElement.parent("div").removeClass("vf__error").find("p.vf__error").remove();
}

ValidFormFieldValidator.prototype.showAlert = function(strAlert) {
	var objElement = jQuery("#" + this.id);
	if (objElement.length == 0) {
		objElement = jQuery("input[name='" + this.id + "']:first").parent().parent();
	}
	
	objElement.parent("div").addClass("vf__error").prepend("<p class=\"vf__error\">" + strAlert + "</p>");
}

/**
 * jQuery.ScrollTo - Easy element scrolling using jQuery.
 * Copyright (c) 2007-2008 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 9/11/2008
 * @author Ariel Flesler
 * @version 1.4
 *
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 */
;(function(h){var m=h.scrollTo=function(b,c,g){h(window).scrollTo(b,c,g)};m.defaults={axis:'y',duration:1};m.window=function(b){return h(window).scrollable()};h.fn.scrollable=function(){return this.map(function(){var b=this.parentWindow||this.defaultView,c=this.nodeName=='#document'?b.frameElement||b:this,g=c.contentDocument||(c.contentWindow||c).document,i=c.setInterval;return c.nodeName=='IFRAME'||i&&h.browser.safari?g.body:i?g.documentElement:this})};h.fn.scrollTo=function(r,j,a){if(typeof j=='object'){a=j;j=0}if(typeof a=='function')a={onAfter:a};a=h.extend({},m.defaults,a);j=j||a.speed||a.duration;a.queue=a.queue&&a.axis.length>1;if(a.queue)j/=2;a.offset=n(a.offset);a.over=n(a.over);return this.scrollable().each(function(){var k=this,o=h(k),d=r,l,e={},p=o.is('html,body');switch(typeof d){case'number':case'string':if(/^([+-]=)?\d+(px)?$/.test(d)){d=n(d);break}d=h(d,this);case'object':if(d.is||d.style)l=(d=h(d)).offset()}h.each(a.axis.split(''),function(b,c){var g=c=='x'?'Left':'Top',i=g.toLowerCase(),f='scroll'+g,s=k[f],t=c=='x'?'Width':'Height',v=t.toLowerCase();if(l){e[f]=l[i]+(p?0:s-o.offset()[i]);if(a.margin){e[f]-=parseInt(d.css('margin'+g))||0;e[f]-=parseInt(d.css('border'+g+'Width'))||0}e[f]+=a.offset[i]||0;if(a.over[i])e[f]+=d[v]()*a.over[i]}else e[f]=d[i];if(/^\d+$/.test(e[f]))e[f]=e[f]<=0?0:Math.min(e[f],u(t));if(!b&&a.queue){if(s!=e[f])q(a.onAfterFirst);delete e[f]}});q(a.onAfter);function q(b){o.animate(e,j,a.easing,b&&function(){b.call(this,r,a)})};function u(b){var c='scroll'+b,g=k.ownerDocument;return p?Math.max(g.documentElement[c],g.body[c]):k[c]}}).end()};function n(b){return typeof b=='object'?b:{top:b,left:b}}})(jQuery);