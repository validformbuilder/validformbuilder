<?php
/***************************
 * ValidForm Builder - build valid and secure web forms quickly
 *
 * Copyright (c) 2009-2013
 *
 * Felix Langfeldt <flangfeldt@felix-it.com>
 * Robin van Baalen <rvanbaalen@felix-it.com>
 *
 * All rights reserved.
 *
 * This software is released under the GNU GPL v2 License <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 *
 * @package    ValidForm
 * @author     Felix Langfeldt <flangfeldt@felix-it.com>, Robin van Baalen <rvanbaalen@felix-it.com>
 * @copyright  2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>, Robin van Baalen <rvanbaalen@felix-it.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link       http://code.google.com/p/validformbuilder/
 ***************************/

define('VFORM_STRING', 1);
define('VFORM_TEXT', 2);
define('VFORM_NUMERIC', 3);
define('VFORM_INTEGER', 4);
define('VFORM_WORD', 5);
define('VFORM_EMAIL', 6);
define('VFORM_PASSWORD', 7);
define('VFORM_SIMPLEURL', 8);
define('VFORM_FILE', 9);
define('VFORM_BOOLEAN', 10);
define('VFORM_CAPTCHA', 11);
define('VFORM_RADIO_LIST', 12);
define('VFORM_CHECK_LIST', 13);
define('VFORM_SELECT_LIST', 14);
define('VFORM_PARAGRAPH', 15);
define('VFORM_CURRENCY', 16);
define('VFORM_DATE', 17);
define('VFORM_CUSTOM', 18);
define('VFORM_CUSTOM_TEXT', 19);
define('VFORM_HTML', 20);
define('VFORM_URL', 21);
define('VFORM_HIDDEN', 22);

define("VFORM_COMPARISON_EQUAL", "equal");
define("VFORM_COMPARISON_NOT_EQUAL", "notequal");
define("VFORM_COMPARISON_EMPTY", "empty");
define("VFORM_COMPARISON_NOT_EMPTY", "notempty");
define("VFORM_COMPARISON_LESS_THAN", "lessthan");
define("VFORM_COMPARISON_GREATER_THAN", "greaterthan");
define("VFORM_COMPARISON_LESS_THAN_OR_EQUAL", "lessthanorequal");
define("VFORM_COMPARISON_GREATER_THAN_OR_EQUAL", "greaterthanorequal");
define("VFORM_COMPARISON_CONTAINS", "contains");
define("VFORM_COMPARISON_STARTS_WITH", "startswith");
define("VFORM_COMPARISON_ENDS_WITH", "endswith");
define("VFORM_COMPARISON_REGEX", "regex");

define("VFORM_MATCH_ALL", "all");
define("VFORM_MATCH_ANY", "any");

?>