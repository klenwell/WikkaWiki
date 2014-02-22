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
 * It is required that this script is run with php-cgi in order to check
 * headers.
 *
 * Usage (run from WikkaWiki root dir):
 * > php-cgi -f test/main/refactor.php
 * 
 */
#
# Change working dir for php-cgi (else file_exists will fail)
# See http://stackoverflow.com/questions/6369064/
#
if ( strpos(php_sapi_name(), 'cgi') > -1 ) {
    $doc_root = dirname(dirname(__DIR__));
    chdir($doc_root);
}

#
# Imports
#
require_once('test/test.config.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
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

# Clear buffer
ob_end_clean();
#print($WikkaMeta['page']['output']);


#
# Tests
#
error_reporting(E_ALL);

function assert_true($assertion, $msg=null) {
    if ( $assertion ) {
        $msg = ( $msg ) ? $msg : 'assert_true passed';
        assert_success($msg);
    }
    else {
        $msg = ( $msg ) ? $msg : 'assert_true failed';
        assert_fail($msg);
    }
}

function assert_equal($val1, $val2) {
    if ( $val1 == $val2 ) {
        assert_success("value [$val1] == [$val2]");
    }
    else {
        assert_fail("value [$val1] != [$val2]");
    }
}

function assert_found($needle, $haystack) {
    if ( strpos($haystack, $needle) !== false ) {
        assert_success("value [$needle] found");
    }
    else {
        assert_fail("value [$needle] not found in:\n$haystack");
    }
}

function assert_not_found($needle, $haystack) {
    if ( strpos($haystack, $needle) === false ) {
        assert_success("value [$needle] not found");
    }
    else {
        assert_fail("value [$needle] found in:\n$haystack");
    }
}

function assert_success($message='no message') {
    $bt = debug_backtrace();
    $caller = $bt[1];
    printf("PASS: %s [%s:%s]\n", $message, basename($caller['file']), $caller['line']);
}

function assert_fail($message=null) {
    $bt = debug_backtrace();
    $caller = $bt[1];
    printf("\nASSERTION FAILED at %s:%d\n", $caller['file'], $caller['line']);
    if ( $message ) {
        print "$message\n";
    }
    print "\nTEST FAILED\n";
    exit(1);
}

$expected_output = array(
    '<title>MyWikkaSite: HelloWorld</title>',
    '<!-- BEGIN PAGE WRAPPER -->',
    '<!-- BEGIN SYSTEM INFO -->',
    $page_tag,
    $page_body
);
foreach( $expected_output as $needle ) {
    assert_found($needle, $WikkaMeta['page']['output']);
}

$unexpected_output = array(
    "This page doesn't exist yet.",
);
foreach( $unexpected_output as $needle ) {
    assert_not_found($needle, $WikkaMeta['page']['output']);
}

# Meta Tests
assert_true($WikkaMeta["page"]["length"] > 4000, 'page length test');

# Test headers sent (cgi version only)
if ( strpos(php_sapi_name(), 'cgi') > -1 ) {
    assert_equal($WikkaMeta["headers"]["length"], 5);
}

#
# Passed!
#
echo "\nNo errors: TEST PASSED!\n";