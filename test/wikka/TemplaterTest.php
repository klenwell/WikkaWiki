<?php
/**
 * wikka/TemplaterTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/wikka/TemplaterTest
 *
 */
require_once('wikka/constants.php');
require_once('version.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('lang/en/en.inc.php');
require_once('libs/Wikka.class.php');
require_once('wikka/templater.php');


class WikkaTemplaterTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->setUpMockServerEnvironment();
        $wikka = new WikkaBlob($this->config);
        $this->templater = new WikkaTemplater($wikka);
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->tearDownSession();
        $this->config = array();
        $this->templater = null;
    }
    
    private function setUpConfig() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        return array_merge($wakkaDefaultConfig, $wakkaConfig);
    }
    
    private function setUpMockServerEnvironment() {
        $_SERVER = array(
            'SERVER_NAME'   => 'localhost',
            'SERVER_PORT'   => '80',
            'QUERY_STRING'  => 'wakka=HomePage',
            'REQUEST_URI'   => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME'   => '/WikkaWiki/wikka.php',
            'PHP_SELF'      => '/WikkaWiki/wikka.php',
            'REMOTE_ADDR'   => '127.0.0.1'
        );
    }
    
    private function tearDownSession() {
        if ( session_id() ) {
            session_destroy();
            $_SESSION = array();
        }
    }
    
    /**
     * Tests
     */
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaTemplater', $this->templater);
    }
}
