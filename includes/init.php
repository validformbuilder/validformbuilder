<?php

/******************************
* Load configuration.
***/
$_CONF = array();
$_CONF['app']['basePath'] 		= dirname(__FILE__) . "/";

/******************************
 * Set default timezone
 ***/
date_default_timezone_set("America/La_Paz");

/******************************
* Set include paths.
***/
$_PATHS['includes'] 		= $GLOBALS["_CONF"]['app']['basePath'] . 'includes/';
$_PATHS['database'] 		= $GLOBALS["_CONF"]['app']['basePath'] . '../db-orm/build/';
$_PATHS['libraries'] 		= $GLOBALS["_CONF"]['app']['basePath'] . '../libraries/';
$_PATHS['validform'] 		= $GLOBALS["_CONF"]['app']['basePath'] . '../libraries/ValidForm/';
$_PATHS['pear']				= $GLOBALS["_CONF"]['app']['basePath'] . '../pear/';

set_include_path(get_include_path() . 
	PATH_SEPARATOR . $GLOBALS["_CONF"]['app']['basePath'] . 
	PATH_SEPARATOR . $_PATHS['pear'] .
	PATH_SEPARATOR . $_PATHS['database'] . "classes" .
	PATH_SEPARATOR . $_PATHS['includes'] .
	PATH_SEPARATOR . $_PATHS['libraries'] .
	PATH_SEPARATOR . $_PATHS['validform']);

// misc functions
function autoload($class_name) {
	$strClass = 'class.' . strtolower($class_name) . '.php';
	if (include_exists($strClass)) {
		require_once($strClass);
		return;
	}

	$strClass = implode('/', explode('_', $class_name)) . '.php';
	if (include_exists($strClass)) {
		require_once($strClass);
		return;
	}
}

spl_autoload_register("autoload");

function include_exists($file) {
   static $include_dirs = null;
   static $include_path = null;

   // set include_dirs
   if (is_null($include_dirs) || get_include_path() !== $include_path) {
	   $include_path    = get_include_path();
	   foreach (explode(PATH_SEPARATOR, $include_path) as $include_dir) {
		   if (substr($include_dir, -1) != '/') {
			   $include_dir .= '/';
		   }
		   
		   $include_dirs[]    = $include_dir;
	   }
   }

   if (substr($file, 0, 1) == '/') { //absolute filepath - what about file:///?
	   return (file_exists($file));
   }

//   if ((substr($file, 0, 7) == 'http://' || substr($file, 0, 6) == 'ftp://') && ini_get('allow_url_fopen')) {
//	   return true;
//   }

   foreach ($include_dirs as $include_dir) {
	   if (file_exists($include_dir.$file)) {
		   return true;
	   }
   }

   return false;
}

/******************************
* Initialize Database.
***/
require_once('Propel/Propel.php');
Propel::init($_PATHS['database'] . "conf/validformtest-conf.php");