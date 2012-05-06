<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 * 
 * Copyright (c) 2009-2012, Felix Langfeldt <flangfeldt@felix-it.com>.
 * All rights reserved.
 * 
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * 
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
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