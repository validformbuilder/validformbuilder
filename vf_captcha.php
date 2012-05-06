<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.opensource.org/licenses/mit-license.php
 * @link       http://code.google.com/p/validformbuilder/
 * @version    Release: 0.2.1
 ***************************/

if (!isset($_SESSION)) session_start();
require_once('libraries/ValidForm/class.phpcaptcha.php');

$arrFonts = array('libraries/ValidForm/fonts/Folks-Normal.ttf');
$intWidth = (array_key_exists("php_captcha_width", $_SESSION)) ? $_SESSION["php_captcha_width"] : 200;
$intHeight = (array_key_exists("php_captcha_height", $_SESSION)) ? $_SESSION["php_captcha_height"] : 60;
$intLength = (array_key_exists("php_captcha_length", $_SESSION)) ? $_SESSION["php_captcha_length"] : 5;

$objCaptcha = new PhpCaptcha($arrFonts, $intWidth, $intHeight);
$objCaptcha->SetNumChars($intLength);
$objCaptcha->create();

?>