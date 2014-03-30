<?php
/**
 * WakkaClassTest.php
 * 
 * Unit tests for Wakka class
 *
 * Generated (in part) by PHPUnit_SkeletonGenerator 1.2.1 on 2014-01-09:
 * > phpunit-skelgen --test -- Wakka libs/Wakka.class.php
 *
 * Tests are divided into sections, which hints at possible paths for
 * dismantling this monstrosity:
 *
 * - General Database Tests
 * - User/Permission Tests
 * - Page Tests
 * - Comment Tests
 * - ACL Tests
 * - Referrer Tests
 * - Spam Tests
 * - Link Table Tests
 * - Cookie/Session Tests
 * - Environment Check Tests
 * - Uncategorized Tests
 * 
 */
require_once('wikka/constants.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wakka.class.php');
require_once('version.php');


class WakkaClassTest extends PHPUnit_Framework_TestCase {
    
    protected static $pdo;
    protected static $wakka;
    protected static $config;
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->config = $this->setUpConfig();
        $this->pdo = $this->setUpDatabase();
        $this->setUpMockServerEnvironment();
        
        $this->wakka = new Wakka($this->config);
        $this->wakka->handler = 'show';
        
        $this->setUpUserTable();
        $this->setUpPagesTable();
        $this->setUpCommentsTable();
        
        $_SERVER['REMOTE_ADDR'] = ( isset($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->wakka = null;
        $this->tearDownDatabase();
        $this->tearDownSession();
        $this->config = array();
    }
    
    private function setUpConfig() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        return array_merge($wakkaDefaultConfig, $wakkaConfig);
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
        
        # Create tables
        require('install/schema.php');
        foreach ($WikkaDatabaseSchema as $key => $sql) {
            $sql = str_replace('{{prefix}}', $this->config['table_prefix'], $sql);
            $sql = str_replace('{{engine}}', 'MyISAM', $sql);
            $sql = str_replace('{{db_name}}', $this->config['mysql_database'], $sql);
            $pdo->exec($sql);
        }
        
        return $pdo;
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
    
    private function setUpUserTable() {
        # User parameters
        $users = array(
            # name, status 
            array('admin', 'active')
        );
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %susers SET name="%s", email="%s", status="%s"';
        
        # Save pages
        foreach ($users as $user) {
            list($name, $status) = $user;
            $email = sprintf('%s@test.wikkawiki.org', $name);
            $this->wakka->query(sprintf($sql_f, $prefix,
                $name, $email, $status));
        }
    }
    
    private function setUpPagesTable() {
        # Page parameters
        $page_tags = array('TestPage1', 'TestPage2', 'TestPage3');
        $page_body = "A test in WakkaClassTest";
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s"';
        
        # Save pages
        foreach ($page_tags as $page_tag) {
            $this->wakka->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        }
    }
    
    private function setUpCommentsTable() {
        # Page parameters
        $page_tag = 'TestPage1';
        $comment_f = "Comment #%d";
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $sql_f = 'INSERT INTO %scomments SET page_tag="%s", comment="%s"';
        
        # Save pages
        for($num=1; $num<=10; $num++) {
            $comment = sprintf($comment_f, $num);
            $this->wakka->query(sprintf($sql_f, $prefix, $page_tag, $comment));
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
     * SECTION: General Database Tests
     */
    /**
     * @covers Wakka::Query
     */
    public function testQuery()
    {
        $page_tag = 'TestQuery';
        $page_body = "A test of the Wakka::Query method";
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        
        # Insert Page
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        $this->assertTrue($result);
        
        # Select Page
        $sql_f = 'SELECT tag, body FROM %spages WHERE tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['tag'], $page_tag);
        $this->assertEquals($row['body'], $page_body);
        
        # Count Pages
        $sql_f = 'SELECT COUNT(*) as count FROM %spages WHERE tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 1);
        
        # Delete Page
        $sql_f = 'DELETE FROM %spages WHERE tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $this->assertTrue($result);
        
        # Count Pages
        $sql_f = 'SELECT COUNT(*) as count FROM %spages WHERE tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 0);
    }

    /**
     * @covers Wakka::LoadAll
     */
    public function testLoadAll()
    {
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $sql_f = 'SELECT tag, body FROM %spages ORDER BY tag ASC';    
        $data = $this->wakka->LoadAll(sprintf($sql_f, $prefix));
        
        $this->assertEquals(count($data), 3);
        $this->assertEquals($data[0]['tag'], 'TestPage1');
    }
    
    /**
     * @covers Wakka::LoadSingle
     */
    public function testLoadSingle()
    {
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $sql_f = 'SELECT tag, body FROM %spages ORDER BY tag ASC';
        $data = $this->wakka->LoadSingle(sprintf($sql_f, $prefix));
        
        $this->assertEquals($data['tag'], 'TestPage1');
        $this->assertEquals($data['body'], 'A test in WakkaClassTest');
    }

    /**
     * @covers Wakka::getCount
     */
    public function testGetCount()
    {
        $count = $this->wakka->getCount('comments', "status IS NULL");
        $this->assertEquals($count, 10);
    }
    
    
    /**
     * SECTION: User/Permission Tests
     */
    /**
     * @todo   Implement testRegisterUser().
     */
    public function testRegisterUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    /**
     * @todo   Implement testLoginUser().
     */
    public function testLoginUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LogoutUser
     * @todo   Implement testLogoutUser().
     */
    public function testLogoutUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    /**
     * @covers Wakka::HasAccess
     * @todo   Implement testHasAccess().
     */
    public function testHasAccess()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    /**
     * @covers Wakka::loadUserData
     * @todo   Implement testLoadUserData().
     */
    public function testLoadUserData()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadUser
     * @todo   Implement testLoadUser().
     */
    public function testLoadUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadUsers
     * @todo   Implement testLoadUsers().
     */
    public function testLoadUsers()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetUserName
     * @todo   Implement testGetUserName().
     */
    public function testGetUserName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetUser
     * @todo   Implement testGetUser().
     */
    public function testGetUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetUser
     * @todo   Implement testSetUser().
     */
    public function testSetUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::existsUser
     * @todo   Implement testExistsUser().
     */
    public function testExistsUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::UserIsOwner
     * @todo   Implement testUserIsOwner().
     */
    public function testUserIsOwner()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::IsAdmin
     * @todo   Implement testIsAdmin().
     */
    public function testIsAdmin()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    
    /**
     * SECTION: Page Tests
     */
    /**
     * @covers Wakka::SavePage
     * @covers Wakka::existsPage
     */
    public function testSavePageAndPageExists()
    {
        # Test params
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $tag = 'TestSavePage';
        $body = 'covers Wakka::SavePage';
        $note = 'also covers Wakka::existsPage';
        $owner = 'TestUser';
        
        # Make page writeable
        # TODO: Fix this. This is a bit unnatural as it implies a page already
        # exists when this test should test creation of a new page, as well
        # as an existing page.
        $sql_f = 'INSERT INTO %sacls SET page_tag="%s", write_acl="*"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $tag));
        
        # Save page
        $this->wakka->SavePage($tag, $body, $note, $owner);
        
        # Verify page created
        $sql_f = 'SELECT COUNT(*) as count FROM %spages WHERE tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 1);
        
        # Test existsPage
        $this->assertTrue($this->wakka->existsPage($tag, $prefix));
        $this->assertFalse($this->wakka->existsPage('PageDoesNotExist', $prefix));
    }

    /**
     * @covers Wakka::GetPageOwner
     * @todo   Implement testGetPageOwner().
     */
    public function testGetPageOwner()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetPageOwner
     * @todo   Implement testSetPageOwner().
     */
    public function testSetPageOwner()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetPageTag
     * @todo   Implement testGetPageTag().
     */
    public function testGetPageTag()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadPage
     * @todo   Implement testLoadPage().
     */
    public function testLoadPage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetPage
     * @todo   Implement testSetPage().
     */
    public function testSetPage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadPageById
     * @todo   Implement testLoadPageById().
     */
    public function testLoadPageById()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadAllPages
     * @todo   Implement testLoadAllPages().
     */
    public function testLoadAllPages()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::PageTitle
     * @todo   Implement testPageTitle().
     */
    public function testPageTitle()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    /**
     * SECTION: Comment Tests
     */
    
    
    /**
     * SECTION: ACL Tests
     */
    /**
     * @covers Wakka::LoadAllACLs
     * @todo   Implement testLoadAllACLs().
     */
    public function testLoadAllACLs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    
    /**
     * SECTION: Referrer Tests
     */
    /**
     * @covers Wakka::LogReferrer
     */
    public function testLogReferrer()
    {
        # Test params
        $prefix = $this->wakka->GetConfigValue('table_prefix');
        $page_tag = 'TestReferredPage';
        $referrer = 'http://delicious.com/';
        
        if ( ! defined('WIKKA_BASE_URL') ) {
            define('WIKKA_BASE_URL', 'http://localhost/');
        }
        
        # Log referrer
        $this->wakka->LogReferrer($page_tag, $referrer);
        
        # Verify referrer logged
        $sql_f = 'SELECT COUNT(*) as count FROM %sreferrers WHERE page_tag="%s"';
        $result = $this->wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 1);
    }
    
    
    /**
     * SECTION: Spam Tests
     */
    
    
    /**
     * SECTION: Link Table Tests
     */
    
    
    
    /**
     * SECTION: Cookie/Session Tests
     */
    /**
     * @covers Wakka::SetSessionCookie
     * @todo   Implement testSetSessionCookie().
     */
    
    
    /**
     * SECTION: Environment Check Tests
     */
    /**
     * @covers Wakka::CheckMySQLVersion
     */
    public function testCheckMySQLVersion()
    {
        $version_greater_than_1 = $this->wakka->CheckMySQLVersion(1, 0, 0);
        $version_greater_than_1000 = $this->wakka->CheckMySQLVersion(1000, 0, 0);
        $this->assertTrue((bool) $version_greater_than_1);
        $this->assertFalse((bool) $version_greater_than_1000);
    }

    /**
     * @covers Wakka::GetWakkaName
     * @todo   Implement testGetWakkaName().
     */
    public function testGetWakkaName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetWakkaVersion
     * @todo   Implement testGetWakkaVersion().
     */
    public function testGetWakkaVersion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    
    /**
     * SECTION: File Operation Tests
     */
    
    
    /**
     * SECTION: Redirect Tests 
     */
    
    
    /**
     * SECTION: Interwiki Tests
     */
    
    
    /**
     * SECTION: Handler Tests
     */
    
    
    /**
     * SECTION: Uncategorized Tests
     */
    /**
     * @covers Wakka::GetMicroTime
     * @covers Wakka::microTimeDiff
     */
    public function testMicroTime()
    {
        $utime_2000 = (float) DateTime::createFromFormat('Y-m-d', '2000-01-01')->format('U');
        $microtime = $this->wakka->GetMicroTime();
        $diff = $this->wakka->microTimeDiff($microtime);
        
        $this->assertGreaterThan($utime_2000, $microtime);
        $this->assertLessThan(.01, $diff);
    }

    /**
     * @covers Wakka::IncludeBuffered
     * @todo   Implement testIncludeBuffered().
     */
    public function testIncludeBuffered()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::ReturnSafeHTML
     * @todo   Implement testReturnSafeHTML().
     */
    public function testReturnSafeHTML()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::cleanUrl
     * @todo   Implement testCleanUrl().
     */
    public function testCleanUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::htmlspecialchars_ent
     * @todo   Implement testHtmlspecialchars_ent().
     */
    public function testHtmlspecialchars_ent()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::hsc_secure
     * @todo   Implement testHsc_secure().
     */
    public function testHsc_secure()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetSafeVar
     * @todo   Implement testGetSafeVar().
     */
    public function testGetSafeVar()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::MiniHref
     * @todo   Implement testMiniHref().
     */
    public function testMiniHref()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Href
     * @todo   Implement testHref().
     */
    public function testHref()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Link
     * @todo   Implement testLink().
     */
    public function testLink()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::StaticHref
     * @todo   Implement testStaticHref().
     */
    public function testStaticHref()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::FormOpen
     * @todo   Implement testFormOpen().
     */
    public function testFormOpen()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::FormClose
     * @todo   Implement testFormClose().
     */
    public function testFormClose()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::BuildFullpathFromMultipath
     * @todo   Implement testBuildFullpathFromMultipath().
     */
    public function testBuildFullpathFromMultipath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}