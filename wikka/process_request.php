<?php
/**
 * main/process_request.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a WikkaRequest class.
 */

#
# Deal with Magic Quotes
# TODO(klenwell): Verify this is even still an issue in PHP5
#
ini_set('magic_quotes_runtime', 0);

if (get_magic_quotes_gpc()) {
    # magicQuotesWorkaround is included during sanity checks
	magicQuotesWorkaround($_POST);
	magicQuotesWorkaround($_GET);
	magicQuotesWorkaround($_COOKIE);
}

#
# Parse URL
#
$RequestedUrl = array(
    'domain'        => $_SERVER['SERVER_NAME'],
    'scheme'        => wikka_parse_scheme(),
    'port'          => wikka_parse_port(),
    'request_path'  => wikka_parse_full_request_path(),
    'query_string'  => $_SERVER['QUERY_STRING'],
    'rewrite_on'    => null,
);

$RequestedUrl = wikka_parse_rewrite_mode($RequestedUrl);

#
# Define Request Constants
#
define_constant_if_not_defined('WIKKA_BASE_DOMAIN_URL', sprintf('%s%s%s',
    $RequestedUrl['scheme'], $RequestedUrl['domain'], $RequestedUrl['port']));

define_constant_if_not_defined('WIKKA_BASE_URL_PATH', preg_replace('/wikka\\.php/',
    '', $_SERVER['SCRIPT_NAME']));

define_constant_if_not_defined('WIKKA_BASE_URL', sprintf('%s%s',
    WIKKA_BASE_DOMAIN_URL, WIKKA_BASE_URL_PATH));

$cookie_path = (WIKKA_BASE_URL_PATH == '/') ? '/' : substr(WIKKA_BASE_URL_PATH, 0, -1);
define('WIKKA_COOKIE_PATH', $cookie_path);

#
# Start Session
#
session_set_cookie_params(0, WIKKA_COOKIE_PATH);
session_name(md5(BASIC_COOKIE_NAME . $wakkaConfig['wiki_suffix']));
session_start();

#
# Make sure CSRFToken set
#
if ( ! isset($_SESSION['CSRFToken']) ) {
    $_SESSION['CSRFToken'] = sha1(getmicrotime());
}

#
# Parse Page and Handler
#
list($page, $handler) = wikka_parse_page_and_handler();
