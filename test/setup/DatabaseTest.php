<?php
/**
 * setup/DatabaseTest.php
 * 
 * Test database setup code. Process detailed below can be used for other tests.
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit test/setup/DatabaseTest
 * 
 */
require_once('test/test.config.php');
require_once('libs/Wakka.class.php');
require_once('version.php');


class DatabaseSetupTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $wakka;
    protected static $config;
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
        global $wikkaTestConfig;
        self::$config = $wikkaTestConfig;
        
        # Must set $config for setup/database.php. Must use require rather than
        # require_once to set up more than one test.
        $config = self::$config;
        require('setup/database.php');
        
        # create db connection
        $host = sprintf('mysql:host=%s', self::$config['mysql_host']);
        self::$pdo = new PDO($host, self::$config['mysql_user'],
            self::$config['mysql_password']);
        self::$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        # create database
        self::$pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`',
            self::$config['mysql_database']));
        self::$pdo->exec(sprintf('CREATE DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo->query(sprintf('USE %s', self::$config['mysql_database']));
        self::$wakka = new Wakka(self::$config);
        
        # create tables
        foreach ($install_queries as $key => $query) {
            self::$pdo->exec($query);
        }
    }
 
    public static function tearDownAfterClass() {
        self::$wakka = NULL;
        
        # cleanup database
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    
    /**
     * Tests
     */
    public function testDatabaseTablesCreates() {        
        $result = self::$pdo->query("SHOW TABLES");
        $this->assertEquals($result->rowCount(), 8);
        
        # collect tables
        $tables = $result->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->assertContains('pages', $tables);
    }
}