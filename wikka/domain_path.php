<?php
/**
 * main/domain_path.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

#
# Attempt to derive base URL fragments and whether rewrite mode is enabled (#438)
#
$t_domain = $_SERVER['SERVER_NAME'];

if ( isset($_SERVER['HTTPS']) && (! empty($_SERVER['HTTPS'])) &&
     ('off' != $_SERVER['HTTPS']) ) {
    $t_scheme = 'https://';
}
else {
    $t_scheme = 'http://';
}

if ( (('http://' == $t_scheme) && (':80' == $_SERVER['SERVER_PORT'])) ||
     (('https://' == $t_scheme) && (':443' == $_SERVER['SERVER_PORT'])) ) {
    $t_port = '';
}
else {
    $t_port = ':'.$_SERVER['SERVER_PORT'];
}

if ( preg_match('@\.php$@', $_SERVER['REQUEST_URI']) &&
     (! preg_match('@wikka\.php$@', $_SERVER['REQUEST_URI'])) ) {
    # handle "overridden" redirect from index.php
    $t_request = preg_replace('@/[^.]+\.php@', '/wikka.php', $_SERVER['REQUEST_URI']);
}
else {
    $t_request	= $_SERVER['REQUEST_URI'];
}

# Check for rewritten request via .htaccess

if ( (! preg_match('@wakka=@', $_SERVER['REQUEST_URI'])) &&
     (isset($_SERVER['QUERY_STRING']) &&
      preg_match('@wakka=@',$_SERVER['QUERY_STRING'])) ) {
	# remove 'wikka.php' and request (page name) from 'request' part: should
	# not be part of base_url!
	$query_part = preg_replace('@wakka=@', '', $_SERVER['QUERY_STRING']);
	$t_request  = preg_replace('@'.preg_quote('wikka.php').'@', '', $t_request);
	$t_request  = preg_replace('@'.preg_quote($query_part).'@', '', $t_request);
	$t_query = '';
	$t_rewrite_mode = 1;
}
else {
	// no rewritten request apparent
	$t_query = '?wakka=';
	$t_rewrite_mode = 0;
}

#
# Define URL Domain / Path
#
# NOTE: Why are we setting $scheme, $server_port, etc. when we just set
# $t_scheme, $t_port above? In fact, they were set exactly the same way!
#
$scheme = $t_scheme;
$server_port = $t_port;

/**
 * URL fragment consisting of scheme + domain part.
 * Represents the domain URL where the current instance of Wikka is located.
 * This variable can be overriden in {@link override.config.php}
 *
 * @var string
 */
define_constant_if_not_defined('WIKKA_BASE_DOMAIN_URL', sprintf('%s%s%s',
    $scheme, $_SERVER['SERVER_NAME'], $server_port));
 
/**
 * URL fragment consisting of a path component.
 * Points to the instance of Wikka within {@link WIKKA_BASE_DOMAIN_URL}.
 *
 * @var string
 */
define('WIKKA_BASE_URL_PATH', preg_replace('/wikka\\.php/', '', $_SERVER['SCRIPT_NAME']));

/**
 * Base URL consisting of {@link WIKKA_BASE_DOMAIN_URL} and {@link WIKKA_BASE_URL_PATH} concatenated.
 * Ready to append a relative path to a "static" file to.
 *
 * @var string
 */
define('WIKKA_BASE_URL', WIKKA_BASE_DOMAIN_URL.WIKKA_BASE_URL_PATH);

/**
 * Path to be used for cookies.
 * Derived from {@link WIKKA_BASE_URL_PATH}
 *
 * @var string
 */
if ( '/' == WIKKA_BASE_URL_PATH ) {
    define('WIKKA_COOKIE_PATH', '/');
}
else {
    define('WIKKA_COOKIE_PATH', substr(WIKKA_BASE_URL_PATH, 0, -1));
}
