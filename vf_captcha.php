<?php
/***************************
 * This file is part of ValidForm Builder - build valid and secure web forms quickly
 * <http://code.google.com/p/validformbuilder/>
 * Copyright (c) 2009 Felix Langfeldt
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 ***************************/

require_once('libraries/ValidForm/class.phpcaptcha.php');

$arrFonts = array('libraries/ValidForm/fonts/Folks-Normal.ttf');
$intWidth = (array_key_exists("php_captcha_width", $_SESSION)) ? $_SESSION["php_captcha_width"] : 200;
$intHeight = (array_key_exists("php_captcha_height", $_SESSION)) ? $_SESSION["php_captcha_height"] : 60;
$intLength = (array_key_exists("php_captcha_length", $_SESSION)) ? $_SESSION["php_captcha_length"] : 5;

$objCaptcha = new PhpCaptcha($arrFonts, $intWidth, $intHeight);
$objCaptcha->SetNumChars($intLength);
$objCaptcha->create();

?>