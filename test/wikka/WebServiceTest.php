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
require_once('version.php');
require_once('wikka/constants.php');
require_once('wikka/functions.php');
require_once('wikka/web_service.php');
require_once('wikka/errors.php');
require_once('libs/Compatibility.lib.php');


class WikkaWebServiceTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {        
        $this->config = $this->setUpConfig();
        $this->pdo = $this->setUpDatabase();
        $this->setUpMockServerEnvironment();
        $this->web_service = new WikkaWebService('test/test.config.php');
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->web_service = null;
        $this->tearDownDatabase();
        $this->tearDownSession();
        $this->config = array();
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
            'REMOTE_ADDR'   => '127.0.0.1'
        );
        
        $_GET = array(
            'wakka'         => 'HomePage'
        );
    }
    
    private function tearDownDatabase() {
        $this->pdo->exec(sprintf('DROP DATABASE `%s`',
            $this->config['mysql_database']));
        $this->pdo = NULL;
    }
    
    private function setUpTables() {
        $config = $this->config;
        require('setup/database.php');
        
        # Create tables
        foreach ($install_queries as $key => $query) {
            $this->pdo->exec($query);
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
    
    private function tearDownSession() {
        if ( session_id() ) {
            session_destroy();
            $_SESSION = array();
        }
    }
    
    
    /**
     * Tests
     */
    public function testInstallInterrupt() {
        # Install not needed
        $this->web_service->interrupt_if_install_required();
        
        # Install needed
        $this->setExpectedException('WikkaInstallInterrupt');
        $this->web_service->config['wakka_version'] = sprintf('un-%s', WAKKA_VERSION);
        $this->web_service->interrupt_if_install_required();
    }
    
    public function testProcessRequest() {
        $page_body = 'Lorem ipsum etc...';
        $page_note = 'for unit test';
        $page_owner = 'TestUser';
        
        $this->setUpTables();
        $this->createPage('HomePage', $page_body, $page_note);
        $this->web_service->prepare_request();
        $this->web_service->start_session();
        $response = $this->web_service->process_request();

        $this->assertEquals(200, $response->status);
        $this->assertNotEmpty($response->headers['etag']);
        $this->assertContains($page_body, $response->body);
    }
    
    public function testRouteRequest() {
        $this->web_service->prepare_request();
        $this->web_service->request->params['wakka'] = 'HomePage/foo';
        $route = $this->web_service->route_request();
        $this->assertEquals($route['page'], 'HomePage');
        $this->assertEquals($route['handler'], 'foo');
    }
    
    public function testCSRFAuthentication() {
        # Init CSRF token
        $this->web_service->prepare_request();
        $this->web_service->start_session();
        $this->web_service->enforce_csrf_token();
        
        # Simulate post
        $_POST['CSRFToken'] = $_SESSION['CSRFToken'];
        $_POST['card'] = 'from the edge';
        
        $this->web_service->prepare_request();
        $token = $this->web_service->enforce_csrf_token();
        $this->assertEquals($_POST['CSRFToken'], $token);
    }
    
    public function testCSRFAuthenticationError() {
        # Init CSRF token
        $this->web_service->prepare_request();
        $this->web_service->start_session();
        $this->web_service->enforce_csrf_token();
        
        # Simulate post with bad token
        $_POST['CSRFToken'] = 'foo';
        $_POST['card'] = 'from the edge';
        
        $this->setExpectedException('WikkaCsrfError');
        $this->web_service->prepare_request();
        $request = $this->web_service->enforce_csrf_token();   # should raise error
    }
    
    public function testPrepareRequest() {
        $request = $this->web_service->prepare_request();
        $this->assertInstanceOf('WikkaRequest', $request);
    }
    
    public function testDisableMagicQuotes() {
        $magic_quotes_are_enabled = $this->web_service->disable_magic_quotes_if_enabled();
        $this->assertFalse($magic_quotes_are_enabled);
    }
    
    public function testLoadConfig() {
        # Test wikka/language_defaults.php loaded
        $this->assertEquals('lang/en', WIKKA_LANG_PATH);
        
        # Test wikka/default.config.php
        $this->assertTrue($this->web_service->config['default_config_loaded']);
        
        # Test test.config.php loaded
        $this->assertArrayHasKey('mysql_database', $this->web_service->config);
        $this->assertNotEmpty($this->web_service->config['mysql_database']);
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaWebService', $this->web_service);
    }
}
