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
 * This script does not test the following modules:
 *  - wikka/multisite.php
 *  - wikka/install.php
 *
 * Usage (run from WikkaWiki root dir):
 * > php-cgi -f test/main/refactor.php
 * 
 */
#
# Change working dir for php-cgi (else file_exists will fail)
# See http://stackoverflow.com/questions/6369064/
#
define('TESTING_AS_CGI', strpos(php_sapi_name(), 'cgi') > -1);

if ( TESTING_AS_CGI ) {
    $doc_root = dirname(dirname(__DIR__));
    chdir($doc_root);
}

#
# Imports
#
require_once('test/helpers.php');
require_once('test/test.config.php');
require_once('wikka/functions_legacy.php');
require_once('wikka/functions.php');
require_once('wikka/constants.php');
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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
$mikka->handler = 'show';

# Page Parameters
$page_tag = 'HelloWorld';
$page_body = "Hello World!";
$page_note = 'for wikka regression testing';
$page_owner = 'TestUser';

# Additional Params
$prefix = $mikka->GetConfigValue('table_prefix');
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80';
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

# Run script
require_once('wikka.php');

# Clear buffer
ob_end_clean();
#print($WikkaMeta['page']['output']);


#
# Tests
#
error_reporting(E_ALL);

#
# Test Page Content
#
$expected_output = array(
    '<title>WakkaWikiTesting: HelloWorld</title>',
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

#
# Test Constants
#
assert_equal(WIKKA_BASE_DOMAIN_URL, 'http://localhost');
assert_equal(WIKKA_LANG_PATH, 'lang/en');
assert_equal(BASIC_COOKIE_NAME, 'Wikkawiki');

if ( TESTING_AS_CGI ) {
    assert_equal(WIKKA_BASE_URL, 'http://localhost');  
}
else {
    # TODO(klenwell): Why the missing / after domain? Doesn't seem to be a
    # issue in production. Root problem is probably a missing $_SERVER var.
    assert_equal(WIKKA_BASE_URL, 'http://localhosttest/main/refactor.php');
    assert_equal(WIKKA_BASE_URL_PATH, 'test/main/refactor.php');
    assert_equal(WIKKA_COOKIE_PATH, 'test/main/refactor.ph');
}

#
# Test Settings
#
assert_equal($wakkaConfig['action_path'], 'plugins/actions,actions');
assert_true(! isset($wakkaConfig['stylesheet']));
assert_equal(session_name(), '96522b217a86eca82f6d72ef88c4c7f4');
assert_equal($page, 'HelloWorld');
assert_equal($handler, '');

# mysql_database should come from test/test.config.php -- change this as necessary
assert_equal($wakkaConfig['mysql_database'], 'wikkawiki_test');

# multisite module is not tested, so this should not be set
assert_true(! isset($multiDefaultConfig));

# install module is not tested, so this should not be set
assert_true(! isset($installAction));

#
# Meta Tests
#
assert_true($WikkaMeta["page"]["length"] > 3800, 'page length test');

# Test headers sent (cgi version only)
if ( TESTING_AS_CGI ) {
    assert_equal($WikkaMeta["headers"]["length"], 5);
}

#
# Passed!
#
echo "\nTEST PASSED!\n";
exit(0);
