<?php
/**
 * main/helpers.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

/**
 * Shamelessly lifted from libs/Wakka.class.php.  See that file for
 * documentation, credits, etc.
 * @see Wakka::htmlspecialchars_ent()
**/
if( ! function_exists('htmlspecialchars_ent') ) {
	function htmlspecialchars_ent($text,$quote_style=ENT_COMPAT,$doctype='HTML') {
		// re-establish default if overwritten because of third parameter
		// [ENT_COMPAT] => 2
		// [ENT_QUOTES] => 3
		// [ENT_NOQUOTES] => 0
		if (!in_array($quote_style,array(ENT_COMPAT,ENT_QUOTES,ENT_NOQUOTES)))
		{
			$quote_style = ENT_COMPAT;
		}

		// define patterns
		$terminator = ';|(?=($|[\n<]|&lt;))';	// semicolon; or end-of-string, newline or tag
		$numdec = '#[0-9]+';					// numeric character reference (decimal)
		$numhex = '#x[0-9a-f]+';				// numeric character reference (hexadecimal)
        
        // pure XML allows only named entities for special chars
		if ($doctype == 'XML') {
			// only valid named entities in XML (case-sensitive)
			$named = 'lt|gt|quot|apos|amp';
			$ignore_case = '';
			$entitystring = $named.'|'.$numdec.'|'.$numhex;
		}
        // (X)HTML
		else {
			$alpha  = '[a-z]+';					// character entity reference TODO $named='eacute|egrave|ccirc|...'
			$ignore_case = 'i';					// names can consist of upper and lower case letters
			$entitystring = $alpha.'|'.$numdec.'|'.$numhex;
		}
        
		$escaped_entity = '&amp;('.$entitystring.')('.$terminator.')';

		$output = Wakka::hsc_secure($text,$quote_style);

		// "repair" escaped entities
		// modifiers: s = across lines, i = case-insensitive
		$output = preg_replace('/'.$escaped_entity.'/s'.$ignore_case,"&$1;",$output);

		// return output
		return $output;
	}
}

/**
 * Shamelessly lifted from libs/Wakka.class.php.  See that file for
 * documentation, credits, etc.
 * @see Wakka::GetSafeVar()
**/
if( ! function_exists('GetSafeVar') ) {
    function GetSafeVar($varname, $gpc='get') {
        $safe_var = NULL;
        
        if ($gpc == 'post') {
            $safe_var = isset($_POST[$varname]) ? $_POST[$varname] : NULL;
        }
        elseif ($gpc == 'get') {
            $safe_var = isset($_GET[$varname]) ? $_GET[$varname] : NULL;
        }
        elseif ($gpc == 'cookie') {
            $safe_var = isset($_COOKIE[$varname]) ? $_COOKIE[$varname] : NULL;
        }
        
        return (htmlspecialchars_ent($safe_var));
    }
}

function define_constant_if_not_defined($name, $value) {
    if ( ! defined($name) ) {
        define($name, $value);
    }
}

function unset_if_isset($var) {
    if ( isset($var) ) {
        unset($var);
    }
}

#
# Install Module Methods
#
function install_or_update_required() {
    global $wakkaConfig;
    return ($wakkaConfig['wakka_version'] !== WAKKA_VERSION);
}

function site_is_locked_for_update() {
    return file_exists('locked');
}

#
# Request Module Methods
#
function wikka_parse_scheme() {
    # See http://stackoverflow.com/a/5100206/1093087
    if ( ! empty($_SERVER['HTTPS']) ) {
        $scheme = 'https://';
    }
    else {
        $scheme = 'http://';
    }
    
    return $scheme;
}

function wikka_parse_port() {
    $scheme = wikka_parse_scheme();
    
    if (($scheme == 'http://') && ($_SERVER['SERVER_PORT'] == '80')) {
        $port = '';
    }
    elseif (($scheme == 'https://') && ($_SERVER['SERVER_PORT'] == '443')) {
        $port = '';
    }
    elseif ( isset($_SERVER['SERVER_PORT']) ) {
        $port = sprintf(':%s', $_SERVER['SERVER_PORT']);
    }
    else {
        $port = '';
    }

    return $port;
}

function wikka_parse_full_request_path() {
    $is_php_uri = preg_match('@\.php$@', $_SERVER['REQUEST_URI']);
    $is_wikka_php_uri = preg_match('@wikka\.php$@', $_SERVER['REQUEST_URI']);
    
    if ( $is_php_uri && (! $is_wikka_php_uri) ) {
        $request_path = preg_replace('@/[^.]+\.php@', '/wikka.php',
            $_SERVER['REQUEST_URI']);
    }
    else {
        $request_path = $_SERVER['REQUEST_URI'];
    }
    
    return $request_path;
}

function wikka_parse_rewrite_mode($parsed_url) {
    $wakka_in_request_uri = preg_match('@wakka=@', $_SERVER['REQUEST_URI']);
    $has_query_string = isset($_SERVER['QUERY_STRING']);
    $wakka_in_query_string = preg_match('@wakka=@',$_SERVER['QUERY_STRING']);
    $in_rewrite_mode = (! $wakka_in_request_uri) && ($has_query_string &&
        $wakka_in_query_string);
    
    if ( $in_rewrite_mode ) {
        # remove 'wikka.php' and request (page name) from 'request' part: should
        # not be part of base_url!        
        $regex = sprintf('@%s@', preg_quote('wikka.php'));
        $parsed_url['request_path'] = preg_replace($regex, '',
            $parsed_url['request_path']);
        
        $query_part = preg_replace('@wakka=@', '', $_SERVER['QUERY_STRING']);
        $regex = sprintf('@%s@', preg_quote($query_part));
        $parsed_url['request_path'] = preg_replace($regex, '',
            $parsed_url['request_path']);
        
        $parsed_url['query_string'] = '';
    }
    else {
        $parsed_url['query_string'] = '?wakka=';
    }
    
    $parsed_url['rewrite_on'] = (int) $in_rewrite_mode;
    
    return $parsed_url;
}

function wikka_parse_page_and_handler() {
    $page = null;
    $handler = null;
    
    # Get wakka param (strip first slash)
    $wakka = GetSafeVar('wakka'); #312
    $wakka = preg_replace("/^\//", "", $wakka);
    
    # Extract pagename and handler from URL
    # Note this splits at the FIRST / so $handler may contain one or more
    # slashes; This is not allowed, and ultimately handled in the Handler()
    # method. [SEC]
    $matches = array();
    if ( preg_match("#^(.+?)/(.*)$#", $wakka, $matches) ) {
        list(, $page, $handler) = $matches;
    }
    elseif ( preg_match("#^(.*)$#", $wakka, $matches) ) {
        list(, $page) = $matches;
    }
    
    # Fix lowercase mod_rewrite bug: URL rewriting makes pagename lowercase. #135
    if ( (isset($_SERVER['REQUEST_URI'])) && (strtolower($page) == $page) ) {
        $pattern = preg_quote($page, '/');
        if ( preg_match("/($pattern)/i", urldecode($_SERVER['REQUEST_URI']),
            $match_url) ) {
            $page = $match_url[1];
        }
    }
    
    return array($page, $handler);
}

function save_session_id_to_db($wikka) {
    $user_name = $wikka->GetUser();
    
    # Only store sessions for logged in users
    if ( is_null($user_name) ) {
        return null;
    }
    
    $table_prefix = $wikka->config['table_prefix'];
    $session_id = session_id();
    
    # Look for current session record
    $query = sprintf('SELECT * FROM %ssessions WHERE sessionid="%s" AND userid="%s"',
        $table_prefix, $session_id, $user_name
    );
    
    $record = $wikka->LoadSingle($query);
    
    # Update session start time
    if ( $record ) {
        $query_f = <<<SQLDOC
UPDATE %ssessions
    SET session_start=FROM_UNIXTIME(%s)
    WHERE sessionid="%s" AND userid="%s"
SQLDOC;

        $query = sprintf($query,
            $table_prefix,
            $wikka->GetMicroTime(),
            $session_id,
            $user_name
        );
    }
    
    # Insert new session
    else {
        $query_f = <<<SQLDOC
INSERT INTO %ssessions (sessionid, userid, session_start)
    VALUES("%s", "%s", FROM_UNIXTIME(%s))
SQLDOC;
    
        $query = sprintf($query_f,
            $table_prefix,
            $session_id,
            $user_name,
            $wikka->GetMicroTime()
        );
    }
    
    # Write to db
    $wikka->Query($query);
    
    return $session_id;
}
