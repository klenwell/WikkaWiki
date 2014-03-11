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
        $config = $this->setUpConfig();
        $this->response = new WikkaResponse($config);
    }
    
    public function tearDown() {
        $this->response = null;
    }
    
    private function setUpConfig() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        return array_merge($wakkaDefaultConfig, $wakkaConfig);
    }
    
    /**
     * Tests
     */
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaResponse', $this->response);
    }
}
