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
 * @author    {@link http://www.mornography.de/ Hendrik Mans}
 * @author    {@link http://wikkawiki.org/JsnX Jason Tourtelotte}
 * @author    {@link http://wikkawiki.org/JavaWoman Marjolein Katsma}
 * @author    {@link http://wikkawiki.org/NilsLindenberg Nils Lindenberg}
 * @author    {@link http://wikkawiki.org/DotMG Mahefa Randimbisoa}
 * @author    {@link http://wikkawiki.org/DarTar Dario Taraborelli}
 * @author    {@link http://wikkawiki.org/BrianKoontz Brian Koontz}
 * @author    {@link http://wikkawiki.org/TormodHaugen Tormod Haugen}
 * @author    {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
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
#
# Imports
#
require_once('libs/Compatibility.lib.php');
require_once('libs/Wakka.class.php');
require_once('wikka/helpers.php');
require_once('wikka/constants.php');

#
# Load Config (sets $wakkaConfig)
#
# Start time: getmicrotime comes from libs/Compatibility.lib.php
global $tstart;
$tstart = getmicrotime();
 
# Load Config
include('wikka/load_config.php');

#
# Install or Upgrade
#
if ( install_or_update_required() ) {
    require_once('wikka/install.php');
}

#
# Process Request (sets $page and $handler)
#
require_once('wikka/process_request.php');

#
# Prepare Response
#
# Start buffer and set Content-Type header (can be overridden by handlers)
ob_start();
header('Content-Type: text/html; charset=utf-8');

# Create Wakka object and assert database access
$wakka = instantiate('Wakka', $wakkaConfig);
$wakka->assert_db_link();
wakka_save_session_id_to_db($wakka);

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
