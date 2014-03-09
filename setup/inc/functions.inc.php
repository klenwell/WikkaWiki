<?php
/**
 * Functions used by the installer/upgrader.
 * 
 * @package	Setup
 * @version	$Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @filesource
 */

/**
 * Brute force copy routine for those sites that have copy() disabled
 *
 * @param string $file1	source file
 * @param string $file2	destination file
 * @return boolean true if successful, false if not
 * @link {http://www.php.net/manual/en/function.copy.php}
 */
function brute_copy($src, $dest)
{
	$context = @file_get_contents($src);
	$newfile = fopen($dest, "w");
	fwrite($newfile, $context);
	fclose($newfile);
	if(FALSE === $context)
		$status = false;
	else
		$status = true;
	return $status;
}

/**
 * Update content of a default page.
 * 
 * If $tag parameter is an array, it just passes elements of this array one by one to itself.
 * The value 'HomePage' is a special one: it will be replaced by the configured value $config['root_page'].
 * The content of the page is read at a file named with $tag, located in setup/default_pages.
 * @param mixed $tag	string or array of strings
 * @param resource $dblink
 * @param mixed $config
 * @param string $lang_defaults_path	mandatory: validated directory for language-specific default pages
 * @param string $lang_defaults_fallback_path	mandatory: validated directory for default pages in system default language
 * @access public
 * @return void
 * @todo avoid recursion: make a single tag into an array of one and then just loop over the tags
 */
function update_default_page($tag, $dblink, $config, $lang_defaults_path, $lang_defaults_fallback_path, $note='')
{
	if (is_array($tag))
	{
		foreach ($tag as $v)
		{
			update_default_page($v, $dblink, $config, $lang_defaults_path, $lang_defaults_fallback_path, $note);
		}
		return;
	}
	
	$filename = $tag;
	if ($tag == '_rootpage')
	{
		$tag = $config['root_page'];
		$filename = 'HomePage';
	}
	$admin_users = explode(',', $config['admin_users']);
	$admin_main_user = trim($admin_users[0]);
	//$txt_filepath = $lang_defaults_path.$filename.'.txt';
	$php_filepath = $lang_defaults_path.$filename.'.php';
	if (!file_exists($php_filepath) || !is_readable($php_filepath))
	{
		$php_filepath = $lang_defaults_fallback_path.$filename.'.php';
	}
	if (file_exists($php_filepath) && is_readable($php_filepath))
	{
		ob_start();
		include_once($php_filepath);
		$body = ob_get_contents();
		ob_end_clean();
		//$body = implode('', file($txt_filepath));
		mysql_query('update '.$config['table_prefix'].'pages set latest = "N" where tag = \''.$tag.'\'', $dblink);
		test (sprintf(__('Adding/Updating default page %s'.'...'), $tag),
			@mysql_query('insert into '.$config['table_prefix'].'pages set tag=\''.$tag.'\', body = \''.mysql_real_escape_string($body).'\', user=\'WikkaInstaller\', owner = \''.$admin_main_user.'\', time=now(), latest =\'Y\', note = \''.mysql_real_escape_string($note).'\'', $dblink),
			'',
			0);
		// @@@ pick up any page-specific ACL here (look in both $lang_defaults_path and $lang_defaults_fallback_path)
	}
	else
	{
		test (sprintf(__('Adding/Updating default page %s'.'...'), $tag), false, sprintf(__('Default page not found or file not readable (%s, %s, %s)'), $tag, $php_filepath, $lang_defaults_path), 0);
	}
}

/**
 * __ .
 * i18n purpose: __() function is actually used to mark certain parts of the installer as translatable strings. This function doesn't echo
 * the string $s, it just returns it. If the string $s contains characters ---<, __() removes it and all strings after it, as if the 
 * serie ---< was a comment marker. Useful if you want to translate very little phrase like 'Do' in 2 situations where its translations may
 * be different! For example: __('Search---<Verb,action'); and __('Search---<Noun').
 * 
 * @param mixed $s 
 * @access public
 * @return void
 */
function __($s)
{
	return (preg_replace('/---<.*$/i', '', $s));
}

/**
 * Facility to echo a <select>...</select> for language packs availables. A simple check is performed on all 
 * subdirectories of the lang/ folder: if a file called xx.inc.php is found inside it, then, it's a valid
 * language pack subfolder. (To avoid treating some obscure system dependent special folders).
 * 
 * @access public
 * @return void
 */
function Language_selectbox($default_lang)
{
	echo '<select name="config[default_lang]">';
	/** @todo fill the array. */
	$human_lang = array (
		'en' => 'English',
		'fr' => 'Français',
		'de' => 'Deutsch',
		'vn' => 'Vietnamese',
		'pl' => 'Polski'
	);
	// use configured path
	$hdl = opendir('lang');
	while ($f = readdir($hdl))
	{
		if ($f[0] == '.') continue;
		if (file_exists('lang'.DIRECTORY_SEPARATOR.$f.DIRECTORY_SEPARATOR.$f.'.inc.php'))
		{
			echo "\n ".'<option value="'.$f.'"';
			if ($f == $default_lang) echo ' selected="selected"';
			echo '>'.(isset($human_lang[$f]) ? $human_lang[$f] : $f).'</option>';
		}
	}
	echo '</select>';
}

// utility functions
function test($text, $condition, $errorText = '<em class="error">Please use your browser\'s back button to correct any errors on the previous page.</em>', $stopOnError = 1) {
	echo $text;
	if ($condition)
	{
		echo ' <span class="ok">OK</span><br />'."\n";
	}
	else
	{
		echo ' <span class="failed">FAILED</span>';
		if ($errorText) echo '<p>'.$errorText.'</p>'."\n";
		echo '<br />'."\n";
		if ($stopOnError)
		{
			include('setup/footer.php');
			exit;
		}
	}
}

function myLocation()
{
	list($url, ) = explode("?", $_SERVER["REQUEST_URI"]);
	return $url;
}

/**
 * Delete a file, or a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.2
 * @param       string   $dirname    Directory to delete
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function rmdirr($dirname)
{
    // Sanity check
    if (!file_exists($dirname)) {
        return false;
    }
 
    // Simple delete for a file
    if (is_file($dirname)) {
        return unlink($dirname);
    }
 
    // Loop through the folder
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
 
        // Recurse
        rmdirr("$dirname/$entry");
    }
 
    // Clean up
    $dir->close();
    return rmdir($dirname);
}

function DeleteCookie($name) { SetCookie($name, "", 1, "/"); $_COOKIE[$name] = ""; }

function SelectTheme($default_theme='light')
{
	echo '<select name="config[theme]">';
	// use configured path
	$hdl = opendir('templates');
	while ($f = readdir($hdl))
	{
		if ($f[0] == '.') continue;
		// use configured path
		else
		{
			echo "\n ".'<option value="'.$f.'"';
			if ($f == $default_theme) echo ' selected="selected"';
			echo '>'.$f.'</option>';
		}
	}
	echo '</select>';
}

function is_authenticated_for_install() {
	# read password from lockfile
	$lines = file("locked");
	$lockpw = trim($lines[0]);
	
	return isset($_SERVER["PHP_AUTH_USER"]) && (
		($_SERVER["PHP_AUTH_USER"] == "admin") &&
		($_SERVER["PHP_AUTH_PW"] == $lockpw)
	);
}
