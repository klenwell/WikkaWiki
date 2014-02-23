<?php
/**
 * main/sanity_checks.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

if ( ! function_exists('version_compare') ||
    version_compare(phpversion(),MINIMUM_PHP_VERSION,'<') ) {
	$php_version_error = sprintf(ERROR_WRONG_PHP_VERSION, MINIMUM_PHP_VERSION);
	die($php_version_error);
}


if ( ! function_exists('mysql_connect') ) {
	die(ERROR_MYSQL_SUPPORT_MISSING);
}


if ( file_exists('libs/Wakka.class.php') ) {
	require_once('libs/Compatibility.lib.php');
	require_once('libs/Wakka.class.php');
}
else {
	die(ERROR_WAKKA_LIBRARY_MISSING);
}
