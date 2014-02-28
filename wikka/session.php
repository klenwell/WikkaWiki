<?php
/**
 * main/session.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

#
# Why are we setting these here when we've already set WIKKA_BASE_URL_PATH
# and WIKKA_COOKIE_PATH in wikka/domain_path.php?
# TODO: Eliminate this redundancy (or explain it).
#
$base_url_path = preg_replace('/wikka\.php/', '', $_SERVER['SCRIPT_NAME']);
$wikka_cookie_path = ('/' == $base_url_path) ? '/' : substr($base_url_path,0,-1);

#
# Start Session
#
session_set_cookie_params(0, $wikka_cookie_path);
session_name(md5(BASIC_COOKIE_NAME.$wakkaConfig['wiki_suffix']));
session_start();

#
# Make sure CSRFToken set
#
if ( ! isset($_SESSION['CSRFToken']) ) {
	$_SESSION['CSRFToken'] = sha1(getmicrotime());
}