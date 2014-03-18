<?php
/**
 * WikkaMigratorTest.php
 * 
 * Unit tests for WikkaMigrator class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/libs/WikkaMigratorTest
 * 
 */
require_once('wikka/constants.php');
require_once('version.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wikka.class.php');
require_once('libs/install/migrator.php');


class WikkaMigratorTest extends PHPUnit_Framework_TestCase {
    
    protected static $config;
    protected static $migrator;
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->migrator = new WikkaMigrator('install/migrations.php');
    }
    
    public function tearDown() {
        $this->config = array();
        $this->migrator = array();
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
        $this->assertInstanceOf('WikkaMigrator', $this->migrator);
        $this->assertEquals('wikkawiki_test', $this->config['mysql_database']);
    }
}