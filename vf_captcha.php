<?php
/**
 * This file is part of ValidFormBuilder.
 *
 * Copyright (c) 2008 Felix Langfeldt
 *
 * ValidFormBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ValidFormBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ValidFormBuilder.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('libraries/ValidForm/class.phpcaptcha.php');

$arrFonts = array('libraries/ValidForm/fonts/Folks-Normal.ttf');
$intWidth = (array_key_exists("php_captcha_width", $_SESSION)) ? $_SESSION["php_captcha_width"] : 200;
$intHeight = (array_key_exists("php_captcha_height", $_SESSION)) ? $_SESSION["php_captcha_height"] : 60;
$intLength = (array_key_exists("php_captcha_length", $_SESSION)) ? $_SESSION["php_captcha_length"] : 5;

$objCaptcha = new PhpCaptcha($arrFonts, $intWidth, $intHeight);
$objCaptcha->SetNumChars($intLength);
$objCaptcha->create();

?>