<?php
/**
 * main/load_config.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */
#
# Imports
#
require_once('version.php');    # Define current Wikka version
 
#
# Config error reporting
#
if ( version_compare(phpversion(),'5.3','<') ) {
    error_reporting(E_ALL);
}
else {
    error_reporting(E_ALL & !E_DEPRECATED);
}

#
# Load config files
#
# Config object 
$wakkaConfig = array();

# Load default config values: $wakkaDefaultConfig
require_once('wikka/default.config.php');

# WAKKA_CONFIG is used by refactor test
if ( defined('WAKKA_CONFIG') ) {
    $config_file = WAKKA_CONFIG;
}
else {
    $config_file = 'wikka.config.php';
}

# If this is fresh install, config file may not yet exist. This will overwrite
# $wakkaConfig
if ( file_exists($config_file) ) {
    include($config_file);
}

#
# Add plugin paths if they do not already exist
#
if ( isset($wakkaConfig['action_path']) &&
     preg_match('/plugins\/actions/', $wakkaConfig['action_path']) <= 0 ) {
	$wakkaConfig['action_path'] = sprintf("plugins/actions,%s",
        $wakkaConfig['action_path']);
}

if ( isset($wakkaConfig['handler_path']) &&
     preg_match('/plugins\/handlers/', $wakkaConfig['handler_path']) <= 0 ) {
	$wakkaConfig['handler_path'] = sprintf("plugins/handlers,%s",
        $wakkaConfig['handler_path']);
}

if ( isset($wakkaConfig['wikka_template_path']) &&
     preg_match('/plugins\/templates/', $wakkaConfig['wikka_template_path']) <= 0 ) {
	$wakkaConfig['wikka_template_path'] = sprintf("plugins/templates,%s",
        $wakkaConfig['wikka_template_path']);
}

if ( isset($wakkaConfig['wikka_formatter_path']) &&
     preg_match('/plugins\/formatters/', $wakkaConfig['wikka_formatter_path']) <= 0 ) {
	$wakkaConfig['wikka_formatter_path'] = sprintf("plugins/formatters,%s",
        $wakkaConfig['wikka_formatter_path']);
}

if ( isset($wakkaConfig['lang_path']) &&
     preg_match('/plugins\/lang/', $wakkaConfig['lang_path']) <= 0 ) {
	$wakkaConfig['lang_path'] = sprintf("plugins/lang,%s", $wakkaConfig['lang_path']);
}

if ( isset($wakkaConfig['menu_config_path']) &&
     preg_match('/plugins\/config/', $wakkaConfig['menu_config_path']) <= 0 ) {
	$wakkaConfig['menu_config_path'] = sprintf("plugins/config,%s",
        $wakkaConfig['menu_config_path']);
}

#
# Merge defaults with config from file: config file settings will overwrite defaults
#
$wakkaConfig = array_merge($wakkaDefaultConfig, $wakkaConfig);

#
# Load Language Defaults
#
require_once('wikka/language_defaults.php');

#
# Multisite Config
#
if ( file_exists('multi.config.php') ) {
    require_once('wikka/multisite.php');
}

#
# Sanity Checks
#
if ( ! function_exists('version_compare') ||
    version_compare(phpversion(),MINIMUM_PHP_VERSION,'<') ) {
	$php_version_error = sprintf(ERROR_WRONG_PHP_VERSION, MINIMUM_PHP_VERSION);
	die($php_version_error);
}

if ( ! function_exists('mysql_connect') ) {
	die(ERROR_MYSQL_SUPPORT_MISSING);
}
