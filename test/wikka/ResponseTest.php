<?php
/**
 * wikka/ResponseTest.php
 * 
 * A test of the WikkaResponse class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/wikka/ResponseTest
 *
 */
require_once('wikka/functions.php');
require_once('wikka/response.php');


class WikkaResponseTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->response = new WikkaResponse();
    }
    
    public function tearDown() {
        $this->response = null;
    }
    
    /**
     * Tests
     */
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaResponse', $this->response);
    }
}
