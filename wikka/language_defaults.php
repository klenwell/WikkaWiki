<?php
/**
 * main/language_defaults.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

// ---------------------------- LANGUAGE DEFAULTS -----------------------------

include_once('localization.php');

/**
 * Include language file(s) if it/they exist(s).
 * @see /lang/en.inc.php
 *
 * Note that all lang_path entries in wikka.config.php are scanned for
 * default_lang files in the order specified in lang_path, with the
 * fallback language pack scanned last to pick up any undefined
 * strings.
 *
 * TODO: Handlers and actions that use their own language packs are
 * responsible for loading their own translation strings.  This
 * process should be unified across the application.
 *
 */
$default_lang = $wakkaConfig['default_lang'];
$fallback_lang = 'en';
$default_lang_path = 'lang'.DIRECTORY_SEPARATOR.$default_lang;
$plugin_lang_path = $wakkaConfig['lang_path'].DIRECTORY_SEPARATOR.$default_lang;
$fallback_lang_path = 'lang'.DIRECTORY_SEPARATOR.$fallback_lang;
$default_lang_strings = $default_lang_path.DIRECTORY_SEPARATOR.$default_lang.'.inc.php';
$plugin_lang_strings = $plugin_lang_path.DIRECTORY_SEPARATOR.$default_lang.'.inc.php';
$fallback_lang_strings = $fallback_lang_path.DIRECTORY_SEPARATOR.$fallback_lang.'.inc.php';
$lang_packs_found = false;

if ( file_exists($plugin_lang_strings) ) {
	require_once($plugin_lang_strings);
	$lang_packs_found = true;
}

if ( file_exists($default_lang_strings) ) {
	require_once($default_lang_strings);
	$lang_packs_found = true;
}

if ( file_exists($fallback_lang_strings) ) {
	require_once($fallback_lang_strings);
	$lang_packs_found = true;
}

if ( ! $lang_packs_found ) {
    $msg_f = <<<HEREDOC
Language file %s not found! In addition, the default language file
%s is missing. Please add the file(s).
HEREDOC;
	die(sprintf($msg_f, $default_lang_strings, $fallback_lang_strings));
}

define_constant_if_not_defined('WIKKA_LANG_PATH', $default_lang_path);

if(!defined('WIKKA_LANG_PATH')) define();
// ------------------------- END LANGUAGE DEFAULTS -----------------------------