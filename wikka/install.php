<?php
/**
 * main/install.php
 * 
 * Module of main wikka.php script
 *
 * The constant WIKKA_INSTALL_TEST is defined by tests to circumvent exits
 * to allow for testing.
 *
 * TODO: replace this module with a method within a request handling object.
 */

$testing_in_progress = defined('WIKKA_INSTALL_TEST') && WIKKA_INSTALL_TEST;

require_once('setup/inc/functions.inc.php');

/**
 * Check for locking.
 */
if ( site_is_locked_for_update() ) {
	if ( ! is_authenticated_for_install() ) {
        $auth_f = 'WWW-Authenticate: Basic realm="% Install/Upgrade Interface"';
        $auth_header = sprintf($auth_f, $wakkaConfig["wakka_name"]);
		
		header($auth_header);
		header("HTTP/1.0 401 Unauthorized");
		
		print T_("This site is currently being upgraded. Please try again later.");
		
		if ( $testing_in_progress ) {
			return;
		}
		else {
			exit;
		}
	}
}

/**
 * Start installer.
 *
 * Data entered by the user is submitted in $_POST, next action for the
 * installer (which will receive this data) is passed as a $_GET parameter!
 */
$installAction = 'default';

if ( isset($_GET['installAction']) ) {
    $installAction = trim(GetSafeVar('installAction'));    #312
}

if ( file_exists('setup'.DIRECTORY_SEPARATOR.'header.php') ) {
    include('setup'.DIRECTORY_SEPARATOR.'header.php');
}
else {
    print '<em class="error">'.ERROR_SETUP_HEADER_MISSING.'</em>'; #89
}

if ( file_exists('setup'.DIRECTORY_SEPARATOR.$installAction.'.php') ) {
    include('setup'.DIRECTORY_SEPARATOR.$installAction.'.php');
}
else {
    print '<em class="error">'.ERROR_SETUP_FILE_MISSING.'</em>'; #89
}

if ( file_exists('setup'.DIRECTORY_SEPARATOR.'footer.php') ) {
    include('setup'.DIRECTORY_SEPARATOR.'footer.php');
}
else {
    print '<em class="error">'.ERROR_SETUP_FOOTER_MISSING.'</em>'; #89
}

if ( ! $testing_in_progress ) {
    exit;
}
