<?php

set_include_path(get_include_path() .
	PATH_SEPARATOR . "/Users/robin/Sites/validformbuilder/trunk/libraries/ValidForm");

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

	$arrClass = explode("\\", $class_name);
	$strClass = "class." . strtolower(array_pop($arrClass)) . ".php";
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

/**
 * Class to hold all randomize logic.
 */
class Random {
	public static function string ($length = 10) {
		$randstr = "";

		for ($i = 0; $i < $length; $i++) {
			$randnum = mt_rand(0,61);

			if($randnum < 10) {
				$randstr .= chr($randnum+48);

			} else if($randnum < 36) {
				$randstr .= chr($randnum+55);

			} else {
				$randstr .= chr($randnum+61);

			}
		}

		return $randstr;
	}
}

if (!defined("VFORM_TEXT")) {
	require_once("vf_constantes.php");
}

echo "Include path set to: " . get_include_path() . "\n\n";
?>