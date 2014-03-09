<?php
/**
 * main/WikkaModulesTest.php
 * 
 * A test of the wikka main modules (post wikka refactor).
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/main/WikkaModulesTest
 *
 * NOTE: must run with --stderr to avoid error:
 *  session_start(): Cannot send session cookie
 *
 * To run all tests:
 * > phpunit --stderr test
 */
define('TESTING_AS_CGI', strpos(php_sapi_name(), 'cgi') > -1);
 
require_once('test/test.config.php');
require_once('wikka/functions_legacy.php');
require_once('wikka/functions.php');
require_once('wikka/constants.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wakka.class.php');
require_once('version.php');


class WikkaModulesTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $wakka;
    protected static $config;
    protected static $default_config;
    protected static $test_paths;
 
    /**
     * Test Fixtures
     */
    public static function setUpBeforeClass() {
        global $wikkaTestConfig, $wakkaDefaultConfig;
        self::$config = $wikkaTestConfig;
        
        # Load default config
        $t_rewrite_mode = 0;    # required by default.config.php
        require_once('wikka/default.config.php');
        self::$default_config = $wakkaDefaultConfig;
        
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
        
        # Path settings
        self::$test_paths = array(
            'configpath' => '/tmp',
        );
        
        # Define Test Flags
        if (! defined('WIKKA_INSTALL_TEST')) {
            define('WIKKA_INSTALL_TEST', 1);
        }
    }
 
    public static function tearDownAfterClass() {       
        # Cleanup database
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        self::$wakka = new Wakka(self::$config);
        self::$wakka->handler = 'show';
        
        $this->multisite_config = 'multi.config.php';
        $this->install_lock_file = 'locked';
        
        $this->save_users();
        $this->save_pages();
        $this->save_comments();
        
        $_SERVER['REMOTE_ADDR'] = ( isset($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
    
    public function tearDown() {
        self::$wakka = NULL;
        
        # Truncate all tables
        foreach (self::$pdo->query('SHOW TABLES') as $row) {
            self::$pdo->query(sprintf('TRUNCATE TABLE %s', $row[0]));
        };
        
        # Delete multisite file if exists
        if ( file_exists($this->multisite_config) ) {
            unlink($this->multisite_config);
        }
        
        # Delete install lock file if exists
        if ( file_exists($this->install_lock_file) ) {
            unlink($this->install_lock_file);
        }
        
        # End session
        if ( isset($_SESSION) ) {
            $_SESSION = array();
        }
        if ( session_id() ) {
            session_destroy();
        }
    }
    
    private function save_users() {
        # User parameters
        $users = array(
            # name, status 
            array('TestAdmin', 'active')
        );
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %susers SET name="%s", email="%s", status="%s"';
        
        # Save pages
        foreach ($users as $user) {
            list($name, $status) = $user;
            $email = sprintf('%s@test.wikkawiki.org', $name);
            self::$wakka->query(sprintf($sql_f, $prefix,
                $name, $email, $status));
        }
    }
    
    private function save_pages() {
        # Page parameters
        $page_tags = array('TestPage1', 'TestPage2', 'TestPage3');
        $page_body = "A test in WakkaClassTest";
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s"';
        
        # Save pages
        foreach ($page_tags as $page_tag) {
            self::$wakka->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        }
    }
    
    private function save_comments() {
        # Page parameters
        $page_tag = 'TestPage1';
        $comment_f = "Comment #%d";
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %scomments SET page_tag="%s", comment="%s"';
        
        # Save pages
        for($num=1; $num<=10; $num++) {
            $comment = sprintf($comment_f, $num);
            self::$wakka->query(sprintf($sql_f, $prefix, $page_tag, $comment));
        }
    }
    
    
    /**
     * Tests
     */
    public function testSaveSessionIdWithUser() {
        # Set Session to Null
        session_start();
        $_SESSION['user'] = 'TestAdmin';
        
        # Load Session module: Creates new sessions       
        $wakka = self::$wakka;
        wakka_save_session_id_to_db($wakka);
        
        # Get sessions
        $query = sprintf('SELECT * FROM %ssessions',
            self::$wakka->config['table_prefix']);
        $sessions = self::$wakka->LoadAll($query);
        $first_session = $sessions[0];
        
        # Asserts
        $this->assertCount(1, $sessions);
        $this->assertEquals($first_session['sessionid'], session_id());
        $this->assertEquals($first_session['userid'], $_SESSION['user']);
        
        # Load Session module: Updates session
        wakka_save_session_id_to_db($wakka);
        
        # Get sessions
        $query = sprintf('SELECT * FROM %ssessions',
            self::$wakka->config['table_prefix']);
        $sessions = self::$wakka->LoadAll($query);
        $second_session = $sessions[0];
        
        # Asserts
        $this->assertCount(1, $sessions);
        $this->assertEquals($second_session['sessionid'], session_id());
        $this->assertEquals($second_session['userid'], $_SESSION['user']);
        $this->assertGreaterThanOrEqual($first_session['session_start'],
            $second_session['session_start']);
    }
    
    public function testSaveSessionIdWithNoUser() {
        # Set Session to Null
        session_start();
        $_SESSION['user'] = NULL;
        
        # Get sessions
        $query = sprintf('SELECT * FROM %ssessions',
            self::$wakka->config['table_prefix']);
        $sessions = self::$wakka->LoadAll($query);
        
        # Assert no session
        $this->assertEmpty($sessions);
    }
    
    public function testInstallModuleWithoutLockedFile() {
        # Load config
        $wakkaConfig = array_merge(self::$config, self::$default_config);
        
        # Set wakka version to trigger install
        $wakkaConfig['wakka_version'] = 0;
        
        # Set additional required values
        $_SERVER["REQUEST_URI"] = '/path?page=HelloWorld';
        
        # Load Module
        ob_start();
        require('wikka/install.php');
        $output = ob_get_contents();
        ob_end_clean();
        
        # Asserts
        $this->assertContains('<title>Wikka Installation</title>', $output);
    }
    
    public function testInstallModulePreAuth() {
        # Load config
        $wakkaConfig = array_merge(self::$config, self::$default_config);
        
        # Set additional required values
        $_SERVER["REQUEST_URI"] = '/path?page=HelloWorld';
        
        # Create lock file
        $lock_file_pw = 'password';
        file_put_contents($this->install_lock_file, $lock_file_pw);
        
        # Load Module
        ob_start();
        require('wikka/install.php');
        $output = ob_get_contents();
        ob_end_clean();
        
        # Asserts ($ask and $lockpw set by wikka/install.php)
        $this->assertTrue(install_or_update_required());
        $this->assertTrue(site_is_locked_for_update());
        $this->assertFalse(is_authenticated_for_install());
        $this->assertContains('This site is currently being upgraded', $output);
    }
    
    public function testInstallAuthentication() {
        # Set auth values
        $_SERVER["PHP_AUTH_USER"] = 'admin';
        $_SERVER["PHP_AUTH_PW"] = 'password';
        
        # Set additional required values
        $_SERVER["REQUEST_URI"] = '/path?page=HelloWorld';
        
        # Create lock file
        $lock_file_pw = $_SERVER["PHP_AUTH_PW"];
        file_put_contents($this->install_lock_file, $lock_file_pw);
        
        # Asserts ($ask and $lockpw set by wikka/install.php)
        $this->assertTrue(install_or_update_required());
        $this->assertTrue(site_is_locked_for_update());
        $this->assertTrue(is_authenticated_for_install());
    }
    
    public function testMultiSiteModule() {
        # Create multisite config (copy test to expected location)
        copy('test/test.config.php', $this->multisite_config);
        
        # Required params
        $t_scheme = 'http://';
        $t_port = '';
        $t_domain = 'wikkawiki.org';
        $wakkaConfig = array(
            'http_wikkawiki_org' => self::$test_paths['configpath'],
        );

        # Load Module
        require('wikka/multisite.php');
        
        # Asserts
        $this->assertEquals($configpath, $multiConfig['http_wikkawiki_org']);
        $this->assertEquals($multiConfig['local_config'], 'wikka.config');
        $this->assertEquals($multiConfig['http_wikkawiki_org'], '/tmp/wikkawiki-test');
        $this->assertEquals($localDefaultConfig['upload_path'],
            '/tmp/wikkawiki-test/uploads');
        $this->assertEquals($wakkaConfig['http_wikkawiki_org'], '/tmp');
        $this->assertEquals($wakkaConfig['upload_path'],
            $localDefaultConfig['upload_path']);
        $this->assertTrue(file_exists($multiConfig['http_wikkawiki_org']));
        $this->assertTrue(file_exists(
            sprintf('%s/actions', $multiConfig['http_wikkawiki_org'])));
        
        # Remove tmp dir
        if ( strpos($multiConfig['http_wikkawiki_org'], '/tmp/') === 0 ) {
            shell_exec(sprintf('rm %s -R', $multiConfig['http_wikkawiki_org']));
        };
    }
    
    public function testWikkaPresence() {
        $this->assertInstanceOf('Wakka', self::$wakka);
    }
}