<?php

require_once("includes/init.php");

$form = DBFormQuery::create()->findOne();
$strForm = $form->getSerialized();

// Before doing anything, make sure validform & validwizard is included.
$dummy = new ValidWizard();

$form = unserialize($strForm);
echo $form->toHtml();

?>