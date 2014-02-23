<?php
/**
 * main/constants.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */
define_constant_if_not_defined('BASIC_COOKIE_NAME', 'Wikkawiki');
define('ID_LENGTH', 10);
define_constant_if_not_defined('PATH_DIVIDER', ',');
define_constant_if_not_defined('MINIMUM_PHP_VERSION', '5.0');
define_constant_if_not_defined('MINIMUM_MYSQL_VERSION', '4.1');
define_constant_if_not_defined('ERROR_WRONG_PHP_VERSION',
    'Wikka requires PHP %s or higher!');

$ERROR_MYSQL_SUPPORT_MISSING = <<<HEREDOC
PHP can't find MySQL support but Wikka requires MySQL. Please check the output
of <tt>phpinfo()</tt> in a php document for MySQL support: it needs to be
compiled into PHP, the module itself needs to be present in the expected
location, <strong>and</strong> php.ini needs to have it enabled.<br />Also note
that you cannot have <tt>mysqli</tt> and <tt>mysql</tt> support both enabled at
the same time.<br />Please double-check all of these things, restart your
webserver after any fixes, and then try again!
HEREDOC;
define_constant_if_not_defined('ERROR_MYSQL_SUPPORT_MISSING', $ERROR_MYSQL_SUPPORT_MISSING);

$ERROR_WAKKA_LIBRARY_MISSING = <<<HEREDOC
The necessary file "libs/Wakka.class.php" could not be found. To run Wikka,
please make sure the file exists and is placed in the right directory!
HEREDOC;
define_constant_if_not_defined('ERROR_WAKKA_LIBRARY_MISSING', $ERROR_WAKKA_LIBRARY_MISSING);
