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
        self::$config = $wikkaTestConfig;
        self::setup_database();
    }
 
    public static function tearDownAfterClass() {
        self::teardown_database();
    }
    
    public static function setup_database() {
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
    
    public static function teardown_database() {
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        $this->web_service = new WikkaWebService('test/test.config.php');
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
