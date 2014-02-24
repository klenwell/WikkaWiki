<?php
/**
 * main/page_handler.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

$page = '';
$handler = '';

#
# Fetch wakka location (requested page + parameters) and remove leading slash
#
# TODO: files action uses POST, everything else uses GET #312
#
# Any reason to use $wakka as var name here when that is the same var that gets
# used for the Wakka class object later?
#
$wakka = GetSafeVar('wakka'); #312
$wakka = preg_replace("/^\//", "", $wakka);

/**
 * Extract pagename and handler from URL
 *
 * Note this splits at the FIRST / so $handler may contain one or more slashes;
 * this is not allowed, and ultimately handled in the Handler() method. [SEC]
 */
if ( preg_match("#^(.+?)/(.*)$#", $wakka, $matches) ) {
    list(, $page, $handler) = $matches;
}
elseif ( preg_match("#^(.*)$#", $wakka, $matches) ) {
    list(, $page) = $matches;
}

# Fix lowercase mod_rewrite bug: URL rewriting makes pagename lowercase. #135
if ( (strtolower($page) == $page) && (isset($_SERVER['REQUEST_URI'])) ) {
	$pattern = preg_quote($page, '/');
	if ( preg_match("/($pattern)/i", urldecode($_SERVER['REQUEST_URI']), $match_url) ) {
		$page = $match_url[1];
	}
}
