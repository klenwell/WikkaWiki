<?php
/**
 * main/magic_quotes.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

ini_set('magic_quotes_runtime', 0);

if (get_magic_quotes_gpc()) {
    # magicQuotesWorkaround is included during sanity checks
	magicQuotesWorkaround($_POST);
	magicQuotesWorkaround($_GET);
	magicQuotesWorkaround($_COOKIE);
}
