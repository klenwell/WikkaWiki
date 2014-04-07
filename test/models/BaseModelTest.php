<?php
/**
 * wikka/BaseModelTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/models/BaseModelTest
 *
 */
require_once('version.php');
require_once('wikka/constants.php');
require_once('models/base.php');


class BaseModelTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->setUpMockServerEnvironment();
        $this->pdo = $this->setUpDatabase();
        
        $this->model = new WikkaModel($this->config);
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->tearDownDatabase();
        $this->tearDownSession();
        $this->config = array();
        $this->model = null;
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
    
    private function setUpDatabase() {
        # Create db connection
        $host = sprintf('mysql:host=%s', $this->config['mysql_host']);
        $pdo = new PDO($host, $this->config['mysql_user'],
            $this->config['mysql_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        # Create database
        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`',
            $this->config['mysql_database']));
        $pdo->exec(sprintf('CREATE DATABASE `%s`',
            $this->config['mysql_database']));
        $pdo->query(sprintf('USE %s', $this->config['mysql_database']));
        
        return $pdo;
    }
    
    private function tearDownDatabase() {
        $this->pdo->exec(sprintf('DROP DATABASE `%s`',
            $this->config['mysql_database']));
        $this->pdo = NULL;
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
    public function testReusableConnection() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testTableSchema() {
        $schema = trim($this->model->get_schema());
        $this->assertStringStartsWith('CREATE TABLE table', $schema);
        $this->assertStringEndsWith('ENGINE=MyISAM', $schema);
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaModel', $this->model);
        $this->assertInstanceOf('PDO', $this->model->pdo);
    }
}
