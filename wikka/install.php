<?php
/**
 * main/install.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

/**
 * Check for locking.
 */
if ( file_exists('locked') ) {
	# read password from lockfile
	$lines = file("locked");
	$lockpw = trim($lines[0]);

	# is authentification given?
	$ask = false;
	if ( isset($_SERVER["PHP_AUTH_USER"]) ) {
		if ( !(($_SERVER["PHP_AUTH_USER"] == "admin") &&
               ($_SERVER["PHP_AUTH_PW"] == $lockpw)) ) {
			$ask = true;
		}
	}
    else {
		$ask = true;
	}

	if ( $ask ) {
        $auth_f = 'WWW-Authenticate: Basic realm="%Install/Upgrade Interface"';
        $auth_header = sprintf($auth_f, $wakkaConfig["wakka_name"]);
		header($auth_header);
		header("HTTP/1.0 401 Unauthorized");
		print T_("This site is currently being upgraded. Please try again later.");
		exit;
	}
}

/**
 * Compare versions, start installer if necessary.
 */
if ( ! isset($wakkaConfig['wakka_version']) ) {
    $wakkaConfig['wakka_version'] = 0;
}

if ( $wakkaConfig['wakka_version'] !== WAKKA_VERSION ) {
	/**
	 * Start installer.
	 *
	 * Data entered by the user is submitted in $_POST, next action for the
	 * installer (which will receive this data) is passed as a $_GET parameter!
	 */
	$installAction = 'default';
    
	if ( isset($_GET['installAction']) ) {
        $installAction = trim(GetSafeVar('installAction'));	#312
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
    
	exit;
}
