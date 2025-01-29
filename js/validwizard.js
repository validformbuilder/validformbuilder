/**
 * ValidWizard javascript class
 * This class extends the ValidForm javascript class with added
 * support for pagination and other fancy wizard stuff.
 *
 * @author Robin van Baalen <robin@stylr.nl>
 *
 * CHANGELOG
 * 	1.0	Initial release
 * 	1.1	Added getValidUntil, setValidUntil, updateValidUntil. getValidUntil returns the latest validated wizard page.
 */
ValidWizard.prototype = new ValidForm();

function ValidWizard(strFormId, strMainAlert, options) {
	if (typeof ValidForm === "undefined") {
		return console.error("ValidForm not included. Cannot initialize ValidWizard without ValidForm.");
	}

	// Inherit ProposalBase's methods in this class.
	ValidForm.apply(this, arguments);

	this._name 			= "ValidWizard";
	this.currentPage 	= jQuery("#" + this.id + " .vf__page:first");
	this.hasConfirmPage = false;
	this.initialPage	= 0;
	this.validUntil 	= this.currentPage.attr("id");

	if (typeof options !== "undefined") {
		if (typeof options.confirmPage !== "undefined" && !!options.confirmPage) {
			this.hasConfirmPage = true;
		}

		if (typeof options.initialPage !== "undefined" && parseInt(options.initialPage) > 0) {
			this.initialPage = parseInt(options.initialPage);
		}
	}
}

/**
 * Internal initialization method
 */
ValidWizard.prototype._init = function () {
	var self = this;

	ValidForm.prototype._init.apply(this, arguments);

	if (typeof self.initialPage > 0) {
		var $objPage = jQuery("#" + this.id + " .vf__page:eq(" + (parseInt(self.initialPage) - 1) + ")");

		this.currentPage.hide();
		this.currentPage = $objPage;
	}
}

ValidWizard.prototype.getValidUntil = function () {
	return this.validUntil;
}

ValidWizard.prototype.setValidUntil = function (strId) {
	this.validUntil = strId;
}

ValidWizard.prototype.updateValidUntil = function () {
	this.validUntil = this.currentPage.next().attr("id");
}

/**
 * This is the deferred initialization method. It get's called when
 * the whole object is filled with fields and ready to use.
 */
ValidWizard.prototype.initialize = function () {
	ValidForm.prototype.initialize.apply(this, arguments);

	this.showPage(this.currentPage);

	if (this.hasConfirmPage) this.addConfirmPage();

	// Get the next & previous labels and set them on all page navigation elements.
	for (var key in this.labels) {
		if (this.labels.hasOwnProperty(key)) {
			if (key == "next" || key == "previous") {
				for (strPageId in this.pages) {
					if (this.pages.hasOwnProperty(strPageId)) {
						$("#" + key + "_" + this.pages[strPageId]).html(this.labels[key]);
						
						if (this.classes.hasOwnProperty(key)) {
							$("#" + key + "_" + this.pages[strPageId]).addClass(this.classes[key]);
						}
					}
				}
			}
		}
	}
}

ValidWizard.prototype.showFirstError = function () {
	var __this = this;

	if (jQuery("#" + __this.id).has(".vf__error").length > 0) {
		var $error 	= jQuery(".vf__error:first");
		var $page 	= $error.parentsUntil(".vf__page").parent();

		__this.currentPage.hide();
		__this.showPage($page);
	}
}

ValidWizard.prototype.showPage = function ($objPage) {
	var self = this;

	if (typeof $objPage == "object" && $objPage instanceof jQuery) {
		jQuery("#" + self.id).trigger("VF_BeforeShowPage", [{ValidForm: self, objPage: $objPage}]);

		if (typeof self.events.beforeShowPage == "function") {
			self.events.beforeShowPage($objPage);
		} else {
			self.cachedEvents.push({"beforeShowPage": $objPage});
		}

		$objPage.show(0, function () {
			jQuery("#" + self.id).trigger("VF_AfterShowPage", [{ValidForm: self, objPage: $objPage}]);
			if (typeof self.events.afterShowPage == "function") {
				self.events.afterShowPage($objPage);
			} else {
				self.cachedEvents.push({"afterShowPage": $objPage});
			}
		});

		// Check if this is the last page.
		// If that is the case, remove the "next" button from the page navigation and show the regular navigation.
		var pageIndex = jQuery("#" + self.id + " .vf__page").index($objPage);
		if (pageIndex > 0 && pageIndex == self.pages.length - 1) {
			jQuery("#" + self.id).find(".vf__navigation").show();
            $objPage.find(".vf__pagenavigation").find("a").not(".vf__previous").remove();
		} else {
			jQuery("#" + self.id).find(".vf__navigation").hide();
		}
	} else {
		throw new Error("Invalid object passed to ValidWizard.showPage().");
	}

	return $objPage;
}

ValidWizard.prototype.addConfirmPage = function () {
	$("#" + this.pages[this.pages.length - 1]).after("<div class='vf__page' id='vf_confirm_" + this.id + "'></div>");
	this.addPage("vf_confirm_" + this.id);

	this.confirmPage = $("vf_confirm_" + this.id);
}

ValidWizard.prototype.addPage = function (strPageId) {
	var __this = this;
	var $page = jQuery("#" + strPageId);
	$page.data("state", "pristine"); // Untouched, fresh new page.

	// Add page to the pages collection
	this.pages.push(strPageId);

	// Add next / prev navigation
	this.addPageNavigation(strPageId);

	// If this is not the first page, hide it.
	$page.hide();
	jQuery("#" + __this.id).find(".vf__navigation").hide();

	jQuery("#" + __this.id).on("change", "#" + strPageId, function () {
		$(this).data("state", "dirty"); // Someone touched this page.
	});

	if (this.pages.length == 1) {
		this.showPage($page);
	}

	if (this.pages.length > 1) {
		var blnIsConfirmPage = (strPageId == "vf_confirm_" + this.id);
		this.addPreviousButton(strPageId, blnIsConfirmPage);
	}
}

ValidWizard.prototype.getLastDirtyPage = function () {
	var lastDirtyPage = null;

	for (var index in this.pages) {
		if (this.pages.hasOwnProperty(index)) {
			var page = this.getPage(this.pages[index]);

			if (page.isDirty()) {
				lastDirtyPage = page;
			}
		}
	}

	return lastDirtyPage;
}

/**
 * This method takes a page ID and returns a page object with properties like:
 *  - ID The page ID
 *  - element A jQuery representation of the page's DOM element
 *  - isLast Method returning a boolean indicating if the current page is the last page
 *  - isFirst Method returning a boolean indicating if the current page is the first page
 *  - isDirty Method returning a boolean indicating if the current page has been touched by the user
 *  - isPristine The opposite of isDirty; indicating this page hasn't been touched by a user yet
 */
ValidWizard.prototype.getPage = function (strPageId) {
	var $element = jQuery("#" + strPageId);
	var self = this;
	var lastPageOffset = (self.hasConfirmPage) ? 2 : 1;

	return {
		id: strPageId,
		element: $element,
		isLast: function () { return (self.pages[self.pages.length - lastPageOffset] === strPageId); },
		isFirst: function () { return (self.pages[0] === strPageId); },
		isDirty: function () { return ($element.data("state") === "dirty"); },
		isPristine: function () { return ($element.data("state") === "pristine"); },
		equals: function (page) { return (page.id === strPageId); }
	};
}

ValidWizard.prototype.addPreviousButton = function (strPageId, blnIsConfirmPage) {
	var __this		= this;
	var $page 		= jQuery("#" + strPageId);

	if ($page.index(".vf__page") > 0) {
		var $pagenav 	= $page.find(".vf__pagenavigation");
		var $nav 		= ($pagenav.length > 0 && !blnIsConfirmPage) ? $pagenav : $("#" + this.id).find(".vf__navigation");

		//*** Call custom event if set.
		jQuery("#" + this.id).trigger("VF_BeforeAddPreviousButton", [__this, {ValidForm: __this, pageId: strPageId}]);
		if (typeof __this.events.beforeAddPreviousButton == "function") {
			__this.events.beforeAddPreviousButton(strPageId);
		}

		$nav.append(jQuery("<a href='#' id='previous_" + strPageId + "' class='vf__button vf__previous'></a>"));

		jQuery("#previous_" + strPageId).on("click", function () {
			__this.previousPage();
			return false;
		});

		//*** Call custom event if set.
		jQuery("#" + this.id).trigger("VF_AfterAddPreviousButton", [{ValidForm: __this, pageId: strPageId}]);
		if (typeof __this.events.afterAddPreviousButton == "function") {
			__this.events.afterAddPreviousButton(strPageId);
		} else {
			this.cachedEvents.push({"afterAddPreviousButton": strPageId});
		}
	}

}

ValidWizard.prototype.getPages = function () {
	var __this 		= this;
	var objReturn 	= {};

	for (i in this.pages) {
		if (this.pages.hasOwnProperty(i)) {
			objReturn[this.pages[i]] = jQuery("#" + __this.pages[i]);
		}
	}

	return objReturn;
}

ValidWizard.prototype.nextPage = function () {
	this.__continueExecution = true; // reset before triggering custom events

	jQuery("#" + this.id).trigger("VF_BeforeNextPage", [{ValidForm: this}]);
	if (this.__continueExecution) {
		if (typeof this.events.beforeNextPage == "function") {
			this.events.beforeNextPage(this);
		}

		if (this.validate("#" + this.currentPage.attr("id"))) {
			this.updateValidUntil();

			if (this.nextIsLast()) {
				jQuery("#" + this.id).trigger("VF_ShowOverview", [{ValidForm: this}]);
			}

			this.currentPage.hide();

			// Set the next page as the new current page.
			this.currentPage = this.currentPage.next(".vf__page");
			this.showPage(this.currentPage);

			jQuery("#" + this.id).trigger("VF_AfterNextPage", [{ValidForm: this}]);
			if (typeof this.events.afterNextPage == "function") {
				this.events.afterNextPage(this);
			}
		}
	}
}

ValidWizard.prototype.nextIsLast = function () {
	var $next = this.currentPage.next(".vf__page");
	var index = (jQuery("#" + this.id + " .vf__page").index($next) + 1);

	return (this.pages.length == index);
}

ValidWizard.prototype.previousPage = function () {
	jQuery("#" + this.id).trigger("VF_BeforePreviousPage", [{ValidForm: this}]);
	if (typeof this.events.beforePreviousPage == "function") {
		this.events.beforePreviousPage(this);
	}

	this.currentPage.hide();

	// Set the next page as the new current page.
	this.currentPage = this.currentPage.prev(".vf__page");
	this.showPage(this.currentPage);

	jQuery("#" + this.id).trigger("VF_AfterPreviousPage", [{ValidForm: this}]);
	if (typeof this.events.afterPreviousPage == "function") {
		this.events.afterPreviousPage(this);
	}
}

ValidWizard.prototype.addPageNavigation = function (strPageId) {
	var __this 			= this;
	//*** Call custom event if set.
	jQuery("#" + this.id).trigger("VF_BeforeAddPageNavigation", [{ValidForm: __this, pageId: strPageId}]);
	if (typeof __this.events.beforeAddPageNavigation == "function") {
		__this.events.beforeAddPageNavigation(strPageId);
	}

	// Button label will be set later in initWizard
	var $page 			= jQuery("#" + strPageId);
	var $nextNavigation = jQuery("<div class='vf__pagenavigation'><a href='#' id='next_" + strPageId + "' class='vf__button'></a></div>");

	jQuery("#" + strPageId).append($nextNavigation);

	jQuery("#next_" + strPageId).on("click", function () {
		__this.nextPage();

		return false;
	});

	//*** Call custom event if set.
	jQuery("#" + this.id).trigger("VF_AfterAddPageNavigation", [{ValidForm: __this, pageId: strPageId}]);
	if (typeof __this.events.afterAddPageNavigation == "function") {
		__this.events.afterAddPageNavigation(strPageId);
	} else {
		this.cachedEvents.push({"afterAddPageNavigation": strPageId});
	}
}
