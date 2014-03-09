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
require_once('wikka/constants.php');
require_once('wikka/functions.php');
require_once('wikka/web_service.php');
require_once('wikka/errors.php');


class WikkaWebServiceTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $config;
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
        include('test/test.config.php');
        self::$config = $wakkaConfig;
        self::setUpDatabase();
    }
 
    public static function tearDownAfterClass() {
        self::tearDownDatabase();
    }
    
    public static function setUpDatabase() {
        # Create db connection
        $host = sprintf('mysql:host=%s', self::$config['mysql_host']);
        self::$pdo = new PDO($host, self::$config['mysql_user'],
            self::$config['mysql_password']);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        # Create database
        self::$pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`',
            self::$config['mysql_database']));
        self::$pdo->exec(sprintf('CREATE DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo->query(sprintf('USE %s', self::$config['mysql_database']));
    }
    
    public static function tearDownDatabase() {
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        $_SERVER = array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'wakka=HomePage',
            'REQUEST_URI' => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME' => '/WikkaWiki/wikka.php'
        );
        
        $this->web_service = new WikkaWebService('test/test.config.php');
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->web_service = null;
    }
    
    /**
     * Tests
     */
    public function testPrepareRequest() {
        $request = $this->web_service->prepare_request();
        $this->assertInstanceOf('WikkaRequest', $request);
    }
    
    public function testDisableMagicQuotes() {
        $magic_quotes_are_enabled = $this->web_service->disable_magic_quotes_if_enabled();
        $this->assertFalse($magic_quotes_are_enabled);
    }
    
    public function testLoadConfig() {
        # Check PDO property (should throw exception if not able to load)
        $this->assertInstanceOf('PDO', $this->web_service->pdo);
        
        # Test wikka/language_defaults.php loaded
        $this->assertEquals('lang/en', WIKKA_LANG_PATH);
        
        # Test wikka/default.config.php
        $this->assertTrue($this->web_service->config['default_config_loaded']);
        
        # Test test.config.php loaded
        $this->assertArrayHasKey('mysql_database', $this->web_service->config);
        $this->assertEquals('wikkawiki_test', $this->web_service->config['mysql_database']);
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaWebService', $this->web_service);
        $this->assertEquals('wikkawiki_test', self::$config['mysql_database']);
    }
}
