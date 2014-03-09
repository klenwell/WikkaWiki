<?php
/**
 * main/WikkaWebServiceTest.php
 * 
 * A test of the WikkaWebService class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/main/WikkaWebServiceTest
 *
 * NOTE: must run with --stderr to avoid error:
 *  session_start(): Cannot send session cookie
 *
 * To run all tests:
 * > phpunit --stderr test
 */
require_once('wikka/web_service.php');


class WikkaWebServiceTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
    }
 
    public static function tearDownAfterClass() {       
    }
    
    public function setUp() {
        $this->web_service = new WikkaWebService();
    }
    
    public function tearDown() {
        $this->web_service = null;
    }
    
    /**
     * Tests
     */
    public function testInstantiation() {
        $this->assertInstanceOf('WikkaWebService', $this->web_service);
    }
}