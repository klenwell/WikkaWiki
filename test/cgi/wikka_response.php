<?php
/**
 * cgi/wikka_response.php
 *
 * Usage (run from WikkaWiki root dir):
 * > php-cgi -f test/cgi/wikka_response.php
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
else {
    throw new Exception("
                        
    This test should be run using php-cgi since it involves testing headers:
    php-cgi -f test/cgi/wikka_response.php
    
    ");
}

#
# Imports
#
require_once('test/helpers.php');
require_once('wikka/response.php');


#
# Test Headers
#
$response = new WikkaResponse();

header('Header1: header');
header('Header2: Two');
$response->set_header('Header1', 'set-header');
$response->merge_php_headers();

assert_true(isset($response->headers['header1']));
assert_true(isset($response->headers['header2']));
assert_equal($response->headers['header1'], 'Header1:  header');
assert_equal($response->headers['header2'], 'Header2:  Two');


#
# Passed!
#
echo "\nTEST PASSED!\n";
exit(0);
