<?php
/**
 * main/constants.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */
function define_constant_if_not_defined($name, $value) {
    if ( ! defined($name) ) {
        define($name, $value);
    }
}

define_constant_if_not_defined('BASIC_COOKIE_NAME', 'Wikkawiki');
define('ID_LENGTH', 10);
define_constant_if_not_defined('PATH_DIVIDER', ',');
define_constant_if_not_defined('MINIMUM_PHP_VERSION', '5.0');
define_constant_if_not_defined('MINIMUM_MYSQL_VERSION', '4.1');
