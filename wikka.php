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
 * @author	{@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 *
 * @copyright Copyright 2002-2003, Hendrik Mans <hendrik@mans.de>
 * @copyright Copyright 2004-2005, Jason Tourtelotte <wikka-admin@jsnx.com>
 * @copyright Copyright 2006-2014, {@link http://wikkawiki.org/CreditsPage Wikka Development Team}
 *
 * @todo use templating class for page generation;
 * @todo add phpdoc documentation for configuration array elements;
 *
 *
 * Klenwell Refactor Notes
 *  Code has been farmed out to modules in wikka dir for cleaner organization.
 *  
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

if ( file_exists('multi.config.php') ) {
    require_once('wikka/multisite.php');
}

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

#
# TODO: refactor of this section has not been well tested. It was not tested
# by the test/main/refactor.php script. Need to test with a user.
#
require_once('wikka/save_session_id.php');


#
# Run engine to generate page output
#
# Add Content-Type header (can be overridden by handlers)
header('Content-Type: text/html; charset=utf-8');

# Run Wikka engine and collect output
$wakka->Run($page, $handler);
$page_output = ob_get_contents();
$page_length = strlen($page_output);

# Send headers
header("Cache-Control: no-cache");
header('ETag: ' . md5($page_output));
header('Content-Length: ' . $page_length);

# Collect data for regression testing
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

#
# Clean buffer and output page 
#
ob_end_clean();
echo $page_output;
