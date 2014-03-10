<?php
/**
 * wikka/RequestTest.php
 * 
 * A test of the WikkaRequest class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/wikka/RequestTest
 *
 * NOTE: must run with --stderr to avoid error:
 *  session_start(): Cannot send session cookie
 *
 * To run all tests:
 * > phpunit --stderr test
 */
require_once('wikka/functions.php');
require_once('wikka/request.php');


class WikkaRequestTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
    }
 
    public static function tearDownAfterClass() {
    }
    
    public function setUp() {
        $_SERVER = array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'wakka=HomePage',
            'REQUEST_URI' => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME' => '/WikkaWiki/wikka.php'
        );
        
        $this->request = new WikkaRequest();
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->request = null;
    }
    
    /**
     * Tests
     */
    public function testDefineConstants() {
        $this->request->define_constants();
        
        $this->assertEquals('http://localhost/WikkaWiki/', WIKKA_BASE_URL);
        $this->assertEquals('http://localhost', WIKKA_BASE_DOMAIN_URL);
        $this->assertEquals('/WikkaWiki/', WIKKA_BASE_URL_PATH);
        $this->assertEquals('/WikkaWiki', WIKKA_COOKIE_PATH);
    }
    
    public function testUrlExtraction() {
        $this->assertEquals($_SERVER['SERVER_NAME'], $this->request->domain);
        $this->assertEquals('http://', $this->request->scheme);
        $this->assertFalse($this->request->rewrite_on);
        $this->assertEquals('/WikkaWiki/wikka.php?wakka=HomePage',
            $this->request->wikka_path);
        $this->assertEquals('?wakka=', $this->request->wikka_query_string);
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaRequest', $this->request);
    }
}
