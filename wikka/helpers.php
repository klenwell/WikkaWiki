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
