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
 * 	- (Init / Default Configuration)
 * 	- DEFINE URL DOMAIN / PATH
 * 	- LOAD CONFIG
 * 	- LANGUAGE DEFAULTS
 * 	- (Multi-site Deployment)
 * 	- (Installer / Setup)
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


// ---------------------------- LANGUAGE DEFAULTS -----------------------------

/**
  * php-gettext
  */
  include_once('localization.php');

/**
 * Include language file(s) if it/they exist(s).
 * @see /lang/en.inc.php
 *
 * Note that all lang_path entries in wikka.config.php are scanned for
 * default_lang files in the order specified in lang_path, with the
 * fallback language pack scanned last to pick up any undefined
 * strings.
 *
 * TODO: Handlers and actions that use their own language packs are
 * responsible for loading their own translation strings.  This
 * process should be unified across the application.
 *
 */
$default_lang = $wakkaConfig['default_lang'];
$fallback_lang = 'en';
$default_lang_path = 'lang'.DIRECTORY_SEPARATOR.$default_lang;
$plugin_lang_path = $wakkaConfig['lang_path'].DIRECTORY_SEPARATOR.$default_lang;
$fallback_lang_path = 'lang'.DIRECTORY_SEPARATOR.$fallback_lang;
$default_lang_strings = $default_lang_path.DIRECTORY_SEPARATOR.$default_lang.'.inc.php';
$plugin_lang_strings = $plugin_lang_path.DIRECTORY_SEPARATOR.$default_lang.'.inc.php';
$fallback_lang_strings = $fallback_lang_path.DIRECTORY_SEPARATOR.$fallback_lang.'.inc.php';
$lang_packs_found = false;
if (file_exists($plugin_lang_strings))
{
	require_once($plugin_lang_strings);
	$lang_packs_found = true;
}
if (file_exists($default_lang_strings))
{
	require_once($default_lang_strings);
	$lang_packs_found = true;
}
if (file_exists($fallback_lang_strings))
{
	require_once($fallback_lang_strings);
	$lang_packs_found = true;
}
if(!$lang_packs_found)
{
	die('Language file '.$default_lang_strings.' not found! In addition, the default language file '.$fallback_lang_strings.' is missing. Please add the file(s).');
}

if(!defined('WIKKA_LANG_PATH')) define('WIKKA_LANG_PATH', $default_lang_path);
// ------------------------- END LANGUAGE DEFAULTS -----------------------------

/**
 * To activate multisite deployment capabilities, just create an empty file multi.config.php in
 * your Wikkawiki installation directory. This file can contain an array definition for
 * $multiConfig.
 * Relevant keys in the array are a global directory for local settings 'local_config' and
 * designated directories for different host requests, e.g. you may want http://example.com
 * and http://www.example.com using the same local config file.
 * 'http_www_example_com' => 'http.example.com'
 * 'http_example_com' => 'http.example.com'
*/
$multisite_configfile = 'multi.config.php';
if (file_exists($multisite_configfile))
{
	$wakkaGlobalConfig = $wakkaConfig;	// copy config file, #878
	$multiDefaultConfig = array(
		'local_config'            => 'wikka.config' # path to local configs
	);
	$multiConfig = array();

    include($multisite_configfile);

    $multiConfig = array_merge($multiDefaultConfig, $multiConfig);    // merge default multi config with config from file

    $configkey = str_replace('://','_',$t_scheme).str_replace('.','_',$t_domain);
    if($t_port != '') $configkey .= '_'.$t_port;


/**
 * Admin can decide to put a specific local config in a more readable and shorter directory.
 * The $configkey is created as 'protocol_thirdleveldomain_secondleveldomain_topleveldomain'
 * Subdirectories are not supported at the moment, but should be easy to implement.
 * If no designated directory is found in multi.config.php, the script uses the $configkey
 * value and replaces all underscore by dots:
 * protocol.thirdleveldomain.secondleveldomain.topleveldomain e.g.
 * http.www.example.com
*/
    if (isset($multiConfig[$configkey])) $configpath = $multiConfig[$configkey];
    else
    {
        $requested_host = str_replace('_','.',$configkey);
        $configpath = $multiConfig['local_config'].DIRECTORY_SEPARATOR.$requested_host;
        $multiConfig[$configkey] = $requested_host;
    }

    $local_configfile = $configpath.DIRECTORY_SEPARATOR.'local.config.php';
/**
 * As each site may differ in its configuration and capabilities, we should consider using
 * plugin directories below the $configpath. Effectively, this replaces the 1.1.6.6 plugins
 * folder. It goes even a little bit further by providing a site specific upload directory.
*/

    $localDefaultConfig = array(
    	'menu_config_path'			=> $configpath.DIRECTORY_SEPARATOR.'config'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'config'.PATH_DIVIDER.'config',
        'action_path'				=> $configpath.DIRECTORY_SEPARATOR.'actions'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'actions'.PATH_DIVIDER.'actions',
        'handler_path'				=> $configpath.DIRECTORY_SEPARATOR.'handlers'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'handlers'.PATH_DIVIDER.'handlers',
        'wikka_formatter_path'		=> $configpath.DIRECTORY_SEPARATOR.'formatters'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'formatters'.PATH_DIVIDER.'formatters',        # (location of Wikka formatter - REQUIRED)
        'wikka_highlighters_path'	=> $configpath.DIRECTORY_SEPARATOR.'formatters'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'formatters'.PATH_DIVIDER.'formatters',        # (location of Wikka code highlighters - REQUIRED)
        'wikka_template_path'		=> $configpath.DIRECTORY_SEPARATOR.'templates'.PATH_DIVIDER.'plugins'.DIRECTORY_SEPARATOR.'templates'.PATH_DIVIDER.'templates',        # (location of Wikka template files - REQUIRED)
        'upload_path'				=> $configpath.DIRECTORY_SEPARATOR.'uploads'
    );
    $localConfig = array();
    if (!file_exists($configpath))
    {
        $path_parts = explode(DIRECTORY_SEPARATOR,$configpath);
        $partialpath = '';
        foreach($path_parts as $part)
        {
            $partialpath .= $part;
            if (!file_exists($partialpath)) mkdir($partialpath,0755);
            $partialpath .= DIRECTORY_SEPARATOR;
        }
        mkdir($configpath.DIRECTORY_SEPARATOR.'config',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'actions',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'handlers',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'handlers'.DIRECTORY_SEPARATOR.'page',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'formatters',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'templates',0700);
        mkdir($configpath.DIRECTORY_SEPARATOR.'uploads',0755);
//        if(file_exists($wakkaConfig['stylesheet'])) copy($wakkaConfig['stylesheet'],$localDefaultConfig['stylesheet']);
    }
    else if (file_exists($local_configfile)) include($local_configfile);

    $wakkaGlobalConfig = array_merge($wakkaGlobalConfig, $localDefaultConfig);    // merge global config with default local config

    $wakkaConfigLocation = $local_configfile;

    $wakkaConfig = array_merge($wakkaGlobalConfig, $wakkaConfig);    // merge localized global config with local config from file
}

/**
 * Check for locking.
 */
if (file_exists('locked'))
{
	// read password from lockfile
	$lines = file("locked");
	$lockpw = trim($lines[0]);

	// is authentification given?
	$ask = false;
	if (isset($_SERVER["PHP_AUTH_USER"])) {
		if (!(($_SERVER["PHP_AUTH_USER"] == "admin") && ($_SERVER["PHP_AUTH_PW"] == $lockpw))) {
			$ask = true;
		}
	} else {
		$ask = true;
	}

	if ($ask) {
		header("WWW-Authenticate: Basic realm=\"".$wakkaConfig["wakka_name"]." Install/Upgrade Interface\"");
		header("HTTP/1.0 401 Unauthorized");
		print T_("This site is currently being upgraded. Please try again later.");
		exit;
	}
}

/**
 * Compare versions, start installer if necessary.
 */
if (!isset($wakkaConfig['wakka_version'])) $wakkaConfig['wakka_version'] = 0;
if ($wakkaConfig['wakka_version'] !== WAKKA_VERSION)
{
	/**
	 * Start installer.
	 *
	 * Data entered by the user is submitted in $_POST, next action for the
	 * installer (which will receive this data) is passed as a $_GET parameter!
	 */
	$installAction = 'default';
	if (isset($_GET['installAction'])) $installAction = trim(GetSafeVar('installAction'));	#312
	if (file_exists('setup'.DIRECTORY_SEPARATOR.'header.php'))
	include('setup'.DIRECTORY_SEPARATOR.'header.php'); else print '<em class="error">'.ERROR_SETUP_HEADER_MISSING.'</em>'; #89
	if
	(file_exists('setup'.DIRECTORY_SEPARATOR.$installAction.'.php'))
	include('setup'.DIRECTORY_SEPARATOR.$installAction.'.php'); else print '<em class="error">'.ERROR_SETUP_FILE_MISSING.'</em>'; #89
	if (file_exists('setup'.DIRECTORY_SEPARATOR.'footer.php'))
	include('setup'.DIRECTORY_SEPARATOR.'footer.php'); else print '<em class="error">'.ERROR_SETUP_FOOTER_MISSING.'</em>'; #89
	exit;
}

/**
 * Start session.
 */
$base_url_path = preg_replace('/wikka\.php/', '', $_SERVER['SCRIPT_NAME']);
$wikka_cookie_path = ('/' == $base_url_path) ? '/' : substr($base_url_path,0,-1);
session_set_cookie_params(0, $wikka_cookie_path);
session_name(md5(BASIC_COOKIE_NAME.$wakkaConfig['wiki_suffix']));
session_start();
if(!isset($_SESSION['CSRFToken']))
{
	$_SESSION['CSRFToken'] = sha1(getmicrotime());
}

// fetch wakka location
/**
 * Fetch wakka location (requested page + parameters)
 *
 * @todo files action uses POST, everything else uses GET #312
 */
$wakka = GetSafeVar('wakka'); #312

/**
 * Remove leading slash.
 */
$wakka = preg_replace("/^\//", "", $wakka);

/**
 * Extract pagename and handler from URL
 *
 * Note this splits at the FIRST / so $handler may contain one or more slashes;
 * this is not allowed, and ultimately handled in the Handler() method. [SEC]
 */
if (preg_match("#^(.+?)/(.*)$#", $wakka, $matches)) list(, $page, $handler) = $matches;
else if (preg_match("#^(.*)$#", $wakka, $matches)) list(, $page) = $matches;
//Fix lowercase mod_rewrite bug: URL rewriting makes pagename lowercase. #135
if ((strtolower($page) == $page) && (isset($_SERVER['REQUEST_URI']))) #38
{
	$pattern = preg_quote($page, '/');
	if (preg_match("/($pattern)/i", urldecode($_SERVER['REQUEST_URI']), $match_url))
	{
		$page = $match_url[1];
	}
}
//$page = preg_replace('/_/', ' ', $page);

/**
 * Create Wakka object
 */
$wakka = instantiate('Wakka',$wakkaConfig);

/**
 * Check for database access.
 */
if (!$wakka->dblink)
{
	echo '<em class="error">'.T_("Error: Unable to connect to the database.").'</em>';
	exit;
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
