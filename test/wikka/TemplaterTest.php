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
require_once('wikka/functions.php');
require_once('wikka/web_service.php');
require_once('wikka/templater.php');


class WikkaTemplaterTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->setUpMockServerEnvironment();
        $this->pdo = $this->setUpDatabase();
        $this->setUpTables();
        $this->createPage('HomePage', 'Hello World!', 'created by setUp');
        
        $web_service = new WikkaWebService('test/test.config.php');
        $web_service->prepare_request();
        $wikka = WikkaBlob::autoload($this->config, 'HomePage');
        
        $this->templater = new WikkaTemplater($wikka);
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->tearDownDatabase();
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
    
    private function setUpTables() {
        $config = $this->config;
        require('setup/database.php');
        
        # Create tables
        foreach ($install_queries as $key => $query) {
            $this->pdo->exec($query);
        }
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
    
    private function createPage($name, $body, $owner="Public", $note='') {
        # Insert page
        $sql_f = 'INSERT INTO %spages (tag, body, owner, note, latest, time) ' .
            'VALUES (:tag, :body, :owner, :note, "Y", NOW())';
        $sql = sprintf($sql_f, $this->config['table_prefix']);
        $query = $this->pdo->prepare($sql);
        $inserted = $query->execute(array(':tag' => $name,
                                          ':body' => $body,
                                          ':owner' => $owner,
                                          ':note' => $note));
        $this->assertTrue($inserted);
        
        # Insert ACLs to make readable
        $sql_f = 'INSERT INTO %sacls (page_tag, write_acl, read_acl) '.
            'VALUES (:tag, "*", "*")';
        $sql = sprintf($sql_f, $this->config['table_prefix']);
        $query = $this->pdo->prepare($sql);
        $inserted = $query->execute(array(':tag' => $name));
        $this->assertTrue($inserted);
    }
    
    /**
     * Tests
     */
    public function testOutputPregReplacements() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaTemplater', $this->templater);
    }
}
