<?php



/**
 * Skeleton subclass for representing a row from the 'dbform' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.validformtest
 */
class DBForm extends BaseDBForm {

	public function getUnserialized() {
		$strSerialized = $this->getSerialized();

		// Load ValidWizard (and ValidForm Builder library)
		$this->loadValidWizard();

		return unserialize($strSerialized);
	}

	/**
	 * For the sake of the autoloader, we fake a call to ValidWizard
	 * which in place will include the whole ValidForm library.
	 */
	protected function loadValidWizard() {
		$dummy = new ValidWizard();
	}
} // DBForm
