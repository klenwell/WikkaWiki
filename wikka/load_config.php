<?php
/**
 * main/load_config.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

$wakkaConfig = array();

# Upgrade from Wakka
if ( file_exists('wakka.config.php') ) {
    rename('wakka.config.php', 'wikka.config.php"');
}

#
# Set $wakkaConfigLocation and load if exists
#
if ( defined('WAKKA_CONFIG') ) {
	$configfile = WAKKA_CONFIG;
}
else {
	$configfile = 'wikka.config.php';
}

$wakkaConfigLocation = $configfile;

# Include emits a warning if file not found (hence the exists check)
if ( file_exists($configfile) ) {
    include($configfile);
}

#
# Remove obsolete config settings (should come before merge!)
#
# TODO move these checks to a directive file to be used by the
# installer/upgrader, #97
#
unset_if_isset($wakkaConfig['header_action']);  # since 1.1.6.4
unset_if_isset($wakkaConfig['footer_action']);  # since 1.1.6.4
unset_if_isset($wakkaConfig['stylesheet']);     # since 1.2 (#6)

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
