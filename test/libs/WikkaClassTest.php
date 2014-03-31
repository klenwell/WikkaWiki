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
require_once('lang/en/en.inc.php');
require_once('libs/Wikka.class.php');
require_once('wikka/functions.php');
require_once('wikka/web_service.php');
require_once('handlers/show.php');


class WikkaBlobTest extends PHPUnit_Framework_TestCase {
    
    protected $config;
    protected $pdo;
    protected $wikka;
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->setUpMockServerEnvironment();
        $this->pdo = $this->setUpDatabase();
        $this->setUpTables();
        
        # Prepare Wikka object
        $this->wikka = new WikkaBlob($this->config);
        $this->wikka->globalize_this_as_wakka_var();
        $this->wikka->connect_to_db();
        $this->wikka->handler = 'show';
        
        # Prepare request
        $web_service = new WikkaWebService('test/test.config.php');
        $web_service->prepare_request();
        
        $this->setUpUsers();
        $this->setUpPages();
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->tearDownDatabase();
        $this->tearDownSession();
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
    
    private function setUpUsers() {
        # User parameters
        $users = array(
            # name, status 
            array('WikkaAdmin', 'active')
        );
        $prefix = $this->wikka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %susers SET name="%s", email="%s", status="%s"';
        
        # Save pages
        foreach ($users as $user) {
            list($name, $status) = $user;
            $email = sprintf('%s@test.wikkawiki.org', $name);
            $this->wikka->query(sprintf($sql_f, $prefix,
                $name, $email, $status));
        }
    }
    
    private function setUpPages() {
        $pages = array(
            array(
                'tag' => 'HelloWorld',
                'body' => 'Hello World',
                'note' => 'first version',
                'owner' => 'WikkaAdmin'
            )
        );
        
        # Page parameters
        foreach ( $pages as $page ) {
            # Insert page
            $sql_f = 'INSERT INTO %spages (tag, body, owner, note, latest, time) ' .
                'VALUES (:tag, :body, :owner, :note, "Y", NOW())';
            $sql = sprintf($sql_f, $this->config['table_prefix']);
            $query = $this->pdo->prepare($sql);
            $inserted = $query->execute(array(':tag' => $page['tag'],
                                              ':body' => $page['body'],
                                              ':owner' => $page['owner'],
                                              ':note' => $page['note']));
            $this->assertTrue($inserted);
            
            # Insert ACLs to make readable
            $sql_f = 'INSERT INTO %sacls (page_tag, write_acl, read_acl) '.
                'VALUES (:tag, "*", "*")';
            $sql = sprintf($sql_f, $this->config['table_prefix']);
            $query = $this->pdo->prepare($sql);
            $inserted = $query->execute(array(':tag' => $page['tag']));
            $this->assertTrue($inserted);
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
    
    
    /**
     * Tests
     */
    public function testShowHandler() {
        # Params
        $handler = 'show';
        $page_tag = 'HelloWorld';
        
        # Set page and ACLs
        $this->wikka->SetPage($this->wikka->LoadPage($page_tag));
        $this->wikka->ACLs = $this->wikka->LoadAllACLs($this->wikka->GetPageTag());
        $this->wikka->ACLs['read_acl'] = '*';
        
        # Test handle
        $response = $this->wikka->Run($page_tag, $handler);
        
        # Test results
        $this->assertInstanceOf('WikkaResponse', $response);
        $this->assertEquals(200, $response->status);
        $this->assertContains('Hello World', $response->body);
    }
    
    public function testWikkaHandlerError() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testGrabCodeHandler() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testRawHandler() {
        # Params
        $handler = 'raw';
        $page_tag = 'HelloWorld';
        
        # Set page and ACLs
        $this->wikka->SetPage($this->wikka->LoadPage($page_tag));
        $this->wikka->ACLs = $this->wikka->LoadAllACLs($this->wikka->GetPageTag());
        $this->wikka->ACLs['read_acl'] = '*';
        
        # Test handle
        $response = $this->wikka->Run($page_tag, $handler);
        
        # Test results
        $this->assertInstanceOf('WikkaResponse', $response);
        $this->assertEquals(0, $response->status);
        $this->assertEquals('Hello World', $response->body);
    }
    
    public function testRecentChangesXmlHandler() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('WikkaBlob', $this->wikka);
        $this->assertNotEmpty($this->config['mysql_database']);
    }
}