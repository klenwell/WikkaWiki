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
require_once('test/test.config.php');
require_once('libs/Compatibility.lib.php');
require_once('./3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wakka.class.php');
require_once('version.php');
require_once('handlers/show.php');


class ShowHandlerTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $wakka;
    protected static $config;
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
        global $wikkaTestConfig;
        self::$config = $wikkaTestConfig;
        
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
 
    public static function tearDownAfterClass() {       
        # Cleanup database
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        $this->wikka = new Wakka(self::$config);
        $this->wikka->handler = 'show';
        $this->show_handler =  new ShowHandler($this->wikka);
        
        $this->save_users();
        $this->save_pages();
        $this->save_comments();
        
        # Need to make $wakka global for formatter
        global $wakka;
        $wakka = $this->wikka;
        
        # GetUserName requires this
        $_SERVER['REMOTE_ADDR'] = ( isset($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
    
    public function tearDown() {
        $this->show_handler = NULL;
        $this->wikka = NULL;
        
        # Truncate all tables
        foreach (self::$pdo->query('SHOW TABLES') as $row) {
            self::$pdo->query(sprintf('TRUNCATE TABLE %s', $row[0]));
        };
    }
    
    private function save_users() {
        # User parameters
        $users = array(
            # name, status 
            array('admin', 'active')
        );
        $prefix = $this->wikka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %susers SET name="%s", email="%s", status="%s"';
        
        # Save pages
        foreach ($users as $user) {
            list($name, $status) = each($user);
            $email = sprintf('%s@test.wikkawiki.org', $name);
            $this->wikka->query(sprintf($sql_f, $prefix,
                $name, $email, $status));
        }
    }
    
    private function save_pages() {
        # Page parameters
        $page_tags = array('TestPage1', 'TestPage2', 'TestPage3');
        $page_body = "A test in WakkaClassTest";
        $prefix = $this->wikka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s", latest="Y", time=NOW()';
        
        # Save pages
        foreach ($page_tags as $page_tag) {
            $this->wikka->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        }
    }
    
    private function save_comments() {
        # Page parameters
        $page_tag = 'TestPage1';
        $comment_f = "Comment #%d";
        $prefix = $this->wikka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %scomments SET page_tag="%s", comment="%s"';
        
        # Save pages
        for($num=1; $num<=10; $num++) {
            $comment = sprintf($comment_f, $num);
            $this->wikka->query(sprintf($sql_f, $prefix, $page_tag, $comment));
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
            '<a href="/edit">create</a> it?';
        
        # Set page and ACLs
        $this->wikka->SetPage($this->wikka->LoadPage(
            $page_tag, $this->wikka->GetSafeVar('time', 'get')));
        $this->wikka->ACLs = $this->wikka->LoadAllACLs($this->wikka->GetPageTag());
        $this->wikka->ACLs['read_acl'] = '*';
        
        # Test handle
        $content = $this->show_handler->handle();
        
        # Test results
        $this->assertFalse($this->wikka->existsPage($page_tag));
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
        $expects = 'A test in <a class="missingpage" href="WakkaClassTest/edit" ' .
            'title="Create this page">WakkaClassTest</a>';
        
        # Set page and ACLs
        $this->wikka->SetPage($this->wikka->LoadPage(
            $page_tag, $this->wikka->GetSafeVar('time', 'get')));
        $this->wikka->ACLs = $this->wikka->LoadAllACLs($this->wikka->GetPageTag());
        $this->wikka->ACLs['read_acl'] = '*';
        
        # Test handle
        $content = $this->show_handler->handle();
        
        # Test results
        $this->assertTrue($this->wikka->existsPage($page_tag));
        $this->assertContains($expects, $content);
    }
    
    public function testHandlerInstantiation() {        
        $this->assertInstanceOf('ShowHandler', $this->show_handler);
        $this->assertEquals($this->show_handler->content_type, 'text/html');
    }
}