/**
 * ValidWizard javascript class
 * This class extends the ValidForm javascript class with added
 * support for pagination and other fancy wizard stuff.
 *
 * @author Robin van Baalen <robin@neverwoods.com>
 */
ValidWizard.prototype = new ValidForm();

function ProposalAuto (strFormId) {

	this._name = "ProposalAuto"; // used for toString() method.
}

function ValidWizard(strFormId, strMainAlert, blnAllowPreviousPage) {
	if (typeof ValidForm === "undefined") {
		return console.error("ValidForm not included. Cannot initialize ValidWizard without ValidForm.");
	}

	this._name = "ValidWizard";

	// Inherit ProposalBase's methods in this class.
	ValidForm.apply(this, arguments);
}

ValidWizard.prototype.init = function () {
	console.log("init");
}