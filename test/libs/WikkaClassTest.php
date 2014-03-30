<?php
/**
 * WikkaClassTest.php
 * 
 * Unit tests for WikkaBlob class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/libs/WikkaClassTest
 *
 * NOTES
 *  - Run run with --stderr to avoid some session errors
 *  - Because of ways constants are used, running this test together with other
 *    test cases may cause unexpected failures.
 * 
 */
require_once('wikka/constants.php');
require_once('version.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wikka.class.php');


class WikkaBlobTest extends PHPUnit_Framework_TestCase {
    
    protected static $config;
    protected static $wikka;
    protected static $pdo;
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->wikka = new WikkaBlob($this->config);
        $this->wikka->handler = 'show';
        
        $this->setUpMockServerEnvironment();
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->config = array();
        $this->wikka = null;
    }
    
    private function setUpConfig() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        return array_merge($wakkaDefaultConfig, $wakkaConfig);
    }
    
    private function setUpMockServerEnvironment() {
        $_SERVER = array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'wakka=HomePage',
            'REQUEST_URI' => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME' => '/WikkaWiki/wikka.php',
            'REMOTE_ADDR' => '127.0.0.1'
        );
    }
    
    
    /**
     * Tests
     */
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaBlob', $this->wikka);
        $this->assertNotEmpty($this->config['mysql_database']);
    }
}