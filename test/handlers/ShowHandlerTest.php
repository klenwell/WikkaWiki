<?php
/**
 * handlers/ShowHandlerTest.php
 * 
 * Test new ShowHandler class.
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/handlers/ShowHandlerTest
 * 
 */
require_once('wikka/constants.php');
require_once('wikka/registry.php');
require_once('wikka/request.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('lang/en/en.inc.php');
require_once('libs/Wakka.class.php');
require_once('version.php');
require_once('handlers/show.php');
require_once('models/base.php');


class ShowHandlerTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $wakka;
    protected static $config;
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
        self::$config = self::setUpConfig();
        
        # Must set $config for setup/database.php
        $config = self::$config;
        require('setup/database.php');
        
        # Create db connection
        $host = sprintf('mysql:host=%s', self::$config['mysql_host']);
        self::$pdo = new PDO($host, self::$config['mysql_user'],
            self::$config['mysql_password']);
        self::$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        # Create database
        self::$pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`',
            self::$config['mysql_database']));
        self::$pdo->exec(sprintf('CREATE DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo->query(sprintf('USE %s', self::$config['mysql_database']));
        
        # Create tables
        foreach ($install_queries as $key => $query) {
            self::$pdo->exec($query);
        }
    }
    
    private static function setUpConfig() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        return array_merge($wakkaDefaultConfig, $wakkaConfig);
    }
    
 
    public static function tearDownAfterClass() {       
        # Cleanup database
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        $this->setUpMockServerEnvironment();
        WikkaRegistry::init(self::$config);
        
        $request = new WikkaRequest();
        $this->show_handler = new ShowHandler($request);
        
        $this->save_users();
        $this->save_pages();
        $this->save_comments();
    }
    
    public function tearDown() {
        $this->show_handler = NULL;
        $this->wikka = NULL;
        
        # Truncate all tables
        foreach (self::$pdo->query('SHOW TABLES') as $row) {
            self::$pdo->query(sprintf('TRUNCATE TABLE %s', $row[0]));
        };
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
            'wakka'         => 'TestPage1'
        );
    }
    
    private function save_users() {
        # User parameters
        $users = array(
            # name, status 
            array('admin', 'active')
        );
        $prefix = self::$config['table_prefix'];
        $sql_f = 'INSERT INTO %susers SET name="%s", email="%s", status="%s"';
        
        # Save pages
        foreach ($users as $user) {
            list($name, $status) = each($user);
            $email = sprintf('%s@test.wikkawiki.org', $name);
            self::$pdo->query(sprintf($sql_f, $prefix,
                $name, $email, $status));
        }
    }
    
    private function save_pages() {
        # Page parameters
        $page_tags = array('TestPage1', 'TestPage2', 'TestPage3');
        $page_body = "A test in WakkaClassTest";
        $prefix = self::$config['table_prefix'];
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s", latest="Y", time=NOW()';
        
        # Save pages
        foreach ($page_tags as $page_tag) {
            self::$pdo->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        }
    }
    
    private function save_comments() {
        # Page parameters
        $page_tag = 'TestPage1';
        $comment_f = "Comment #%d";
        $prefix = self::$config['table_prefix'];
        $sql_f = 'INSERT INTO %scomments SET page_tag="%s", comment="%s"';
        
        # Save pages
        for($num=1; $num<=10; $num++) {
            $comment = sprintf($comment_f, $num);
            self::$pdo->query(sprintf($sql_f, $prefix, $page_tag, $comment));
        }
    }
    
    
    /**
     * Tests
     */
    public function testPageWithComments() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testPageDoesNotExist() {
        # Params
        $page_tag = 'PageNotFound';
        $expects = 'This page doesn\'t exist yet. Maybe you want to ' .
            '<a href="http://localhost/WikkaWiki/wikka.php?wakka=PageNotFound">' .
            'create</a> it?';
            
        # Prepare Handler
        $_GET['wakka'] = $page_tag;
        $request = new WikkaRequest();
        $show_handler = new ShowHandler($request);
        
        # Handle
        $content = $show_handler->handle();
        
        # Test results
        $page = PageModel::find_by_tag($page_tag);
        $this->assertFalse($page->exists());
        $this->assertContains($expects, $content);
    }
    
    public function testInvalidPageName() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testNoReadAccess() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testPageWithRevisionInfo() {
        $this->markTestIncomplete('TODO');
    }
    
    public function testValidRequest() {
        # Params
        $page_tag = 'TestPage1';
        $expects = 'A test in <a class="missingpage" ' .
            'href="http://localhost/WikkaWiki/wikka.php?wakka=WakkaClassTest/edit" ' .
            'title="Create this page">WakkaClassTest</a>';
            
        # Prepare Handler
        $_GET['wakka'] = $page_tag;
        $request = new WikkaRequest();
        $request->define_constants();
        $show_handler = new ShowHandler($request);
        
        # Handle
        $response = $show_handler->handle();
        
        # Test results
        $page = PageModel::find_by_tag($page_tag);
        $this->assertInstanceOf('WikkaResponse', $response);
        $this->assertTrue($page->exists());
        $this->assertContains($expects, $response->body);
    }
    
    public function testHandlerInstantiation() {        
        $this->assertInstanceOf('ShowHandler', $this->show_handler);
        $this->assertEquals($this->show_handler->content_type, 'text/html; charset=utf-8');
    }
}