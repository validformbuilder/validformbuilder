<?php
namespace ValidFormBuilder;

/**
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
 * @package ValidForm
 * @author Felix Langfeldt <flangfeldt@felix-it.com>, Robin van Baalen <rvanbaalen@felix-it.com>
 * @copyright 2009-2012 Felix Langfeldt <flangfeldt@felix-it.com>, Robin van Baalen <rvanbaalen@felix-it.com>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 * @link http://code.google.com/p/validformbuilder/
 */
const VFORM_STRING = 1;
const VFORM_TEXT = 2;
const VFORM_NUMERIC = 3;
const VFORM_INTEGER = 4;
const VFORM_WORD = 5;
const VFORM_EMAIL = 6;
const VFORM_PASSWORD = 7;
const VFORM_SIMPLEURL = 8;
const VFORM_FILE = 9;
const VFORM_BOOLEAN = 10;
const VFORM_CAPTCHA = 11;
const VFORM_RADIO_LIST = 12;
const VFORM_CHECK_LIST = 13;
const VFORM_SELECT_LIST = 14;
const VFORM_PARAGRAPH = 15;
const VFORM_CURRENCY = 16;
const VFORM_DATE = 17;
const VFORM_CUSTOM = 18;
const VFORM_CUSTOM_TEXT = 19;
const VFORM_HTML = 20;
const VFORM_URL = 21;
const VFORM_HIDDEN = 22;

const VFORM_COMPARISON_EQUAL = "equal";
const VFORM_COMPARISON_NOT_EQUAL = "notequal";
const VFORM_COMPARISON_EMPTY = "empty";
const VFORM_COMPARISON_NOT_EMPTY = "notempty";
const VFORM_COMPARISON_LESS_THAN = "lessthan";
const VFORM_COMPARISON_GREATER_THAN = "greaterthan";
const VFORM_COMPARISON_LESS_THAN_OR_EQUAL = "lessthanorequal";
const VFORM_COMPARISON_GREATER_THAN_OR_EQUAL = "greaterthanorequal";
const VFORM_COMPARISON_CONTAINS = "contains";
const VFORM_COMPARISON_STARTS_WITH = "startswith";
const VFORM_COMPARISON_ENDS_WITH = "endswith";
const VFORM_COMPARISON_REGEX = "regex";

const VFORM_MATCH_ALL = "all";
const VFORM_MATCH_ANY = "any";
