<?php
/**
 * The Wikka mainscript.
 *
 * This file is called each time a request is made from the browser.
 * Most of the core methods used by the engine are located in the Wakka class.
 * @see Wakka
 * This file was originally written by Hendrik Mans for WakkaWiki
 * and released under the terms of the modified BSD license
 * @see /docs/WakkaWiki.LICENSE
 *
 * @package Wikka
 * @subpackage Core
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @see /docs/Wikka.LICENSE
 * @filesource
 *
 * @author	{@link http://www.mornography.de/ Hendrik Mans}
 * @author	{@link http://wikkawiki.org/JsnX Jason Tourtelotte}
 * @author	{@link http://wikkawiki.org/JavaWoman Marjolein Katsma}
 * @author	{@link http://wikkawiki.org/NilsLindenberg Nils Lindenberg}
 * @author	{@link http://wikkawiki.org/DotMG Mahefa Randimbisoa}
 * @author	{@link http://wikkawiki.org/DarTar Dario Taraborelli}
 * @author	{@link http://wikkawiki.org/BrianKoontz Brian Koontz}
 * @author	{@link http://wikkawiki.org/TormodHaugen Tormod Haugen}
 *
 * @copyright Copyright 2002-2003, Hendrik Mans <hendrik@mans.de>
 * @copyright Copyright 2004-2005, Jason Tourtelotte <wikka-admin@jsnx.com>
 * @copyright Copyright 2006-2010, {@link http://wikkawiki.org/CreditsPage Wikka Development Team}
 *
 * @todo use templating class for page generation;
 * @todo add phpdoc documentation for configuration array elements;
 *
 *
 * Klenwell Refactor Notes
 *  Currently Marked Sections:
 * 	- (Start Session)
 * 	- (Set $wakka location var)
 * 	- (Set Page & Handler)
 * 	- (Create Wakka object)
 * 	- (Save session ID)
 * 	- (Run the engine)
 * 	- (Output page)
 */

require_once('wikka/error_reporting.php');

require_once('version.php');    # Define current Wikka version

require_once('wikka/helpers.php');

require_once('wikka/constants.php');

require_once('wikka/sanity_checks.php');

#
# Start buffering and a timer (why start timer here and not sooner?)
#
ob_start();
global $tstart;
$tstart = getmicrotime();

require_once('wikka/magic_quotes.php');

require_once('wikka/domain_path.php');

require_once('wikka/default.config.php');

require_once('wikka/load_config.php');

require_once('wikka/language_defaults.php');

#
# TODO: refactor of this section has not been well tested. It was not tested
# by the test/main/refactor.php script.
#
if ( file_exists('multi.config.php') ) {
    require_once('wikka/multisite.php');
}

#
# TODO: refactor of this section has not been well tested. It was not tested
# by the test/main/refactor.php script.
#
require_once('wikka/install.php');

require_once('wikka/session.php');

require_once('wikka/page_handler.php');


#
# Create Wakka object and assert database access
#
$wakka = instantiate('Wakka', $wakkaConfig);

if ( ! $wakka->dblink ) {
    die(sprintf('<em class="error">%s</em>',
        T_("Error: Unable to connect to the database.")));
}


/**
 * Save session ID
 */
$user = $wakka->GetUser();
// Only store sessions for real users!
if(NULL != $user)
{
	$res = $wakka->LoadSingle("SELECT * FROM ".$wakka->config['table_prefix']."sessions WHERE sessionid='".session_id()."' AND userid='".$user['name']."'");
	if(isset($res))
	{
		// Just update the session_start time
		$wakka->Query("UPDATE ".$wakka->config['table_prefix']."sessions SET session_start=FROM_UNIXTIME(".$wakka->GetMicroTime().") WHERE sessionid='".session_id()."' AND userid='".$user['name']."'");
	}
	else
	{
		// Create new session record
		$wakka->Query("INSERT INTO ".$wakka->config['table_prefix']."sessions (sessionid, userid, session_start) VALUES('".session_id()."', '".$user['name']."', FROM_UNIXTIME(".$wakka->GetMicroTime()."))");
	}
}

/**
 * Run the engine.
 */
if (!isset($handler)) $handler='';

# Add Content-Type header (can be overridden by handlers)
header('Content-Type: text/html; charset=utf-8');

$wakka->Run($page, $handler);
$content =  ob_get_contents();
/**
 * Use gzip compression if possible.
 */
/*
if ( isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) #38
{
	// Tell the browser the content is compressed with gzip
	header ("Content-Encoding: gzip");
	$page_output = gzencode($content);
	$page_length = strlen($page_output);
} else {
 */
	$page_output = $content;
	$page_length = strlen($page_output);
//}

// header("Cache-Control: pre-check=0");
header("Cache-Control: no-cache");
// header("Pragma: ");
// header("Expires: ");

$etag =  md5($content);
header('ETag: '.$etag);
header('Content-Length: '.$page_length);

#
# Collect data for regression testing
#
$WikkaMeta = array(
    'headers' => array(
        'sent' => (int) headers_sent(),
        'list' => headers_list(),
        'length' => count(headers_list())
    ),
    'page' => array(
        'output' => $page_output,
        'length' => $page_length,
    ),
);

ob_end_clean();

/**
 * Output the page.
 */
echo $page_output;
?>
