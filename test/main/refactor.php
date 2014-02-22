<?php
/**
 * main/refactor.php
 * 
 * Provides a simple regression for testing wikka.php script while refactoring
 * main script.
 *
 * This is not part of the phpunit suite. (I wasn't able to get phpunit to
 * play nicely with wikka.php's buffering.)
 *
 * Usage (run from WikkaWiki root dir):
 * > php -f test/main/refactor.php
 * 
 */
#
# Imports
#
require_once('test/test.config.php');
require_once('libs/Compatibility.lib.php');
require_once('./3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wakka.class.php');
require_once('version.php');


#
# Setup Database
#
# Must set $config for setup/database.php. $wikkaTestConfig from test/test.config.php
$config = $wikkaTestConfig; 
require('setup/database.php');

# Create db connection
$host = sprintf('mysql:host=%s', $config['mysql_host']);
$pdo = new PDO($host, $config['mysql_user'],
    $config['mysql_password']);
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

# Create database
$pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`',
    $config['mysql_database']));
$pdo->exec(sprintf('CREATE DATABASE `%s`',
    $config['mysql_database']));
$pdo->query(sprintf('USE %s', $config['mysql_database']));

# Create tables
foreach ($install_queries as $key => $query) {
    $pdo->exec($query);
}


#
# Create Simple Page 
#
# Requires a wikka object: $mikka
$mikka = new Wakka($config);

# Page Parameters
$page_tag = 'HelloWorld';
$page_body = "Hello World!";
$page_note = 'for wikka regression testing';
$page_owner = 'TestUser';

# Additional Params
$prefix = $mikka->GetConfigValue('table_prefix');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';  # Need to set this manually
$_GET['wakka'] = $page_tag;             # How wikka knows what page is wanted

# Need to set some constants
define('WIKKA_MAIN_TEST', true);
define('WAKKA_CONFIG', 'test/test.config.php');

# Make page readable and writeable in ACL table (doesn't matter that page
# does not yet exist!)
$sql_f = 'INSERT INTO %sacls SET page_tag="%s", write_acl="*", read_acl="*"';
$result = $mikka->query(sprintf($sql_f, $prefix, $page_tag));

# Save page
$mikka->SavePage($page_tag, $page_body, $page_note, $page_owner);

# Create output buffer
ob_start();
ob_start();

# Run script
require_once('wikka.php');

# Capture Output
$content =  ob_get_contents();
ob_end_clean();


#
# Tests
#
error_reporting(E_ALL);

$expected_output = array(
    # array(string, expected)
    # Expected
    array('<title>MyWikkaSite: HelloWorld</title>', true),
    array('<!-- BEGIN PAGE WRAPPER -->', true),
    array('<!-- BEGIN SYSTEM INFO -->', true),
    array($page_body, true),
    
    # Unexpected
    array("This page doesn't exist yet.", false),
);

foreach ( $expected_output as $expectation ) {
    list($value, $expected) = $expectation;
    
    if ( $expected && strpos($content, $value) < 0) {
        echo $content;
        throw new Exception(sprintf("FAILED TO FIND: %s", $value));
    }
    elseif ( (! $expected) && strpos($content, $value) > -1 )  {
        echo $content;
        throw new Exception(sprintf("FAILED IN FINDING: %s", $value));
    }
    else {
        printf("PASS: [%s] %s\n", $value, ($expected) ? 'found' : 'not found');
    }
}

# This test may be too strict. Strlen should remain constant during refactoring
# but could easily 
#echo $content;
$expected_strlen = 4559;
assert('strlen($content) == $expected_strlen');


#
# Passed!
#
echo "\nNo errors: TEST PASSED!\n";