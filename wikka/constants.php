<?php
/**
 * wikka/constants.php
 * 
 * Module of main wikka.php script
 *
 * This should be the first module loaded.
 */

define('WIKKA_ERROR_LEVEL', E_ALL ^ E_DEPRECATED);
define('MINIMUM_PHP_VERSION', '5.0');
define('MINIMUM_MYSQL_VERSION', '4.1');
define('ERROR_WRONG_PHP_VERSION', 'Wikka requires PHP %s or higher!');
 
define('WIKKA_CONFIG_PATH', 'wikka.config.php');
define('WIKKA_DEFAULT_CONFIG_PATH', 'wikka/default.config.php');
define('WIKKA_MULTI_CONFIG_PATH', 'multi.config.php');

define('WIKKA_LIBRARY_PATH', 'lib');
define('PATH_DIVIDER', ',');

define('BASIC_COOKIE_NAME', 'Wikkawiki');
define('DEFAULT_COOKIE_EXPIRATION_HOURS', 90 * 24);

define('ID_LENGTH', 10);
define('SHOW_INVALID_CHARS', '| ? = &lt; &gt; / \ " % &amp;');

$ERROR_MYSQL_SUPPORT_MISSING = <<<HEREDOC
PHP can't find MySQL support but Wikka requires MySQL. Please check the output
of <tt>phpinfo()</tt> in a php document for MySQL support: it needs to be
compiled into PHP, the module itself needs to be present in the expected
location, <strong>and</strong> php.ini needs to have it enabled.<br />Also note
that you cannot have <tt>mysqli</tt> and <tt>mysql</tt> support both enabled at
the same time.<br />Please double-check all of these things, restart your
webserver after any fixes, and then try again!
HEREDOC;
define('ERROR_MYSQL_SUPPORT_MISSING', $ERROR_MYSQL_SUPPORT_MISSING);

$ERROR_WAKKA_LIBRARY_MISSING = <<<HEREDOC
The necessary file "libs/Wakka.class.php" could not be found. To run Wikka,
please make sure the file exists and is placed in the right directory!
HEREDOC;
define('ERROR_WAKKA_LIBRARY_MISSING', $ERROR_WAKKA_LIBRARY_MISSING);
