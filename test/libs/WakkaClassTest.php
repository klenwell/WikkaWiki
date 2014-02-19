<?php
/**
 * WakkaClassTest.php
 * 
 * Unit tests for Wakka class
 *
 * Generated (in part) by PHPUnit_SkeletonGenerator 1.2.1 on 2014-01-09:
 * > phpunit-skelgen --test -- Wakka libs/Wakka.class.php
 * 
 */
require_once('test/test.config.php');
require_once('libs/Compatibility.lib.php');
require_once('libs/Wakka.class.php');
require_once('version.php');


class WakkaClassTest extends PHPUnit_Framework_TestCase {
    
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
        self::$wakka = new Wakka(self::$config);
        
        # Create tables
        foreach ($install_queries as $key => $query) {
            self::$pdo->exec($query);
        }
    }
 
    public static function tearDownAfterClass() {
        self::$wakka = NULL;
        
        # Cleanup database
        self::$pdo->exec(sprintf('DROP DATABASE `%s`',
            self::$config['mysql_database']));
        self::$pdo = NULL;
    }
    
    public function setUp() {
        $this->save_pages();
        $this->save_comments();
    }
    
    public function tearDown() {
        # Truncate all tables
        foreach (self::$pdo->query('SHOW TABLES') as $row) {
            self::$pdo->query(sprintf('TRUNCATE TABLE %s', $row[0]));
        };
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
     * SECTION: Query Tests
     */
    /**
     * @covers Wakka::Query
     */
    public function testQuery()
    {
        $page_tag = 'TestQuery';
        $page_body = "A test of the Wakka::Query method";
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        
        # Insert Page
        $sql_f = 'INSERT INTO %spages SET tag="%s", body="%s"';
        $result = self::$wakka->query(sprintf($sql_f, $prefix, $page_tag, $page_body));
        $this->assertTrue($result);
        
        # Select Page
        $sql_f = 'SELECT tag, body FROM %spages WHERE tag="%s"';
        $result = self::$wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['tag'], $page_tag);
        $this->assertEquals($row['body'], $page_body);
        
        # Count Pages
        $sql_f = 'SELECT COUNT(*) as count FROM %spages WHERE tag="%s"';
        $result = self::$wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 1);
        
        # Delete Page
        $sql_f = 'DELETE FROM %spages WHERE tag="%s"';
        $result = self::$wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $this->assertTrue($result);
        
        # Count Pages
        $sql_f = 'SELECT COUNT(*) as count FROM %spages WHERE tag="%s"';
        $result = self::$wakka->query(sprintf($sql_f, $prefix, $page_tag));
        $row = mysql_fetch_assoc($result);
        $this->assertEquals($row['count'], 0);
    }

    /**
     * @covers Wakka::LoadAll
     */
    public function testLoadAll()
    {
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $sql_f = 'SELECT tag, body FROM %spages ORDER BY tag ASC';    
        $data = self::$wakka->LoadAll(sprintf($sql_f, $prefix));
        
        $this->assertEquals(count($data), 3);
        $this->assertEquals($data[0]['tag'], 'TestPage1');
    }

    /**
     * @covers Wakka::getCount
     * @todo   Implement testGetCount().
     */
    public function testGetCount()
    {
        $count = self::$wakka->getCount('comments', "status IS NULL");
        $this->assertEquals($count, 10);
    }
    
    /**
     * @covers Wakka::SavePage
     * @covers Wakka::existsPage
     */
    public function testSavePageAndPageExists()
    {
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $tag = 'TestSavePage';
        $body = 'covers Wakka::SavePage';
        $note = 'also covers Wakka::existsPage';
        
        self::$wakka->SavePage($tag, $body, $note);
        $this->assertTrue(self::$wakka->existsPage($tag, $prefix));
    }

    /**
     * @covers Wakka::WriteLinkTable
     * @todo   Implement testWriteLinkTable().
     */
    public function testWriteLinkTable()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LogReferrer
     * @todo   Implement testLogReferrer().
     */
    public function testLogReferrer()
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
     * @covers Wakka::SaveComment
     * @todo   Implement testSaveComment().
     */
    public function testSaveComment()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::deleteComment
     * @todo   Implement testDeleteComment().
     */
    public function testDeleteComment()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
     * @covers Wakka::SaveACL
     * @todo   Implement testSaveACL().
     */
    public function testSaveACL()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Maintenance
     * @todo   Implement testMaintenance().
     */
    public function testMaintenance()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    
    /**
     * SECTION: Load Tests
     */
    /**
     * @covers Wakka::LoadSingle
     * @covers Wakka::LoadAll
     */
    public function testLoadSingle()
    {
        $prefix = self::$wakka->GetConfigValue('table_prefix');
        $sql_f = 'SELECT tag, body FROM %spages ORDER BY tag ASC';
        $data = self::$wakka->LoadSingle(sprintf($sql_f, $prefix));
        
        $this->assertEquals($data['tag'], 'TestPage1');
        $this->assertEquals($data['body'], 'A test in WakkaClassTest');
    }
    
    
    /**
     * SECTION: Utility Tests
     */
    /**
     * @covers Wakka::CheckMySQLVersion
     */
    public function testCheckMySQLVersion()
    {
        $version_greater_than_1 = self::$wakka->CheckMySQLVersion(1, 0, 0);
        $version_greater_than_1000 = self::$wakka->CheckMySQLVersion(1000, 0, 0);
        $this->assertTrue((bool) $version_greater_than_1);
        $this->assertFalse((bool) $version_greater_than_1000);
    }
    
    /**
     * @covers Wakka::GetMicroTime
     * @covers Wakka::microTimeDiff
     */
    public function testMicroTime()
    {
        $utime_2000 = (float) DateTime::createFromFormat('Y-m-d', '2000-01-01')->format('U');
        $microtime = self::$wakka->GetMicroTime();
        $diff = self::$wakka->microTimeDiff($microtime);
        
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
     * @covers Wakka::makeId
     * @todo   Implement testMakeId().
     */
    public function testMakeId()
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
     * @covers Wakka::GeSHi_Highlight
     * @todo   Implement testGeSHi_Highlight().
     */
    public function testGeSHi_Highlight()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::normalizeLines
     * @todo   Implement testNormalizeLines().
     */
    public function testNormalizeLines()
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
     * @covers Wakka::GetPageTime
     * @todo   Implement testGetPageTime().
     */
    public function testGetPageTime()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetHandler
     * @todo   Implement testGetHandler().
     */
    public function testGetHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetConfigValue
     * @todo   Implement testGetConfigValue().
     */
    public function testGetConfigValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetConfigValue
     * @todo   Implement testSetConfigValue().
     */
    public function testSetConfigValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
     * @covers Wakka::GetWikkaPatchLevel
     * @todo   Implement testGetWikkaPatchLevel().
     */
    public function testGetWikkaPatchLevel()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::logSpamComment
     * @todo   Implement testLogSpamComment().
     */
    public function testLogSpamComment()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::logSpamDocument
     * @todo   Implement testLogSpamDocument().
     */
    public function testLogSpamDocument()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::logSpamFeedback
     * @todo   Implement testLogSpamFeedback().
     */
    public function testLogSpamFeedback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::logSpam
     * @todo   Implement testLogSpam().
     */
    public function testLogSpam()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::getSpamlogSummary
     * @todo   Implement testGetSpamlogSummary().
     */
    public function testGetSpamlogSummary()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::readFile
     * @todo   Implement testReadFile().
     */
    public function testReadFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::writeFile
     * @todo   Implement testWriteFile().
     */
    public function testWriteFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::appendFile
     * @todo   Implement testAppendFile().
     */
    public function testAppendFile()
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
     * @covers Wakka::GetCachedPage
     * @todo   Implement testGetCachedPage().
     */
    public function testGetCachedPage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::CachePage
     * @todo   Implement testCachePage().
     */
    public function testCachePage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::HasPageTitle
     * @todo   Implement testHasPageTitle().
     */
    public function testHasPageTitle()
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
     * @covers Wakka::SetPageTitle
     * @todo   Implement testSetPageTitle().
     */
    public function testSetPageTitle()
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
     * @covers Wakka::LoadRevisions
     * @todo   Implement testLoadRevisions().
     */
    public function testLoadRevisions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadOldestRevision
     * @todo   Implement testLoadOldestRevision().
     */
    public function testLoadOldestRevision()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadPagesLinkingTo
     * @todo   Implement testLoadPagesLinkingTo().
     */
    public function testLoadPagesLinkingTo()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadRecentlyChanged
     * @todo   Implement testLoadRecentlyChanged().
     */
    public function testLoadRecentlyChanged()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadWantedPages
     * @todo   Implement testLoadWantedPages().
     */
    public function testLoadWantedPages()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::IsWantedPage
     * @todo   Implement testIsWantedPage().
     */
    public function testIsWantedPage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadOrphanedPages
     * @todo   Implement testLoadOrphanedPages().
     */
    public function testLoadOrphanedPages()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadPageTitles
     * @todo   Implement testLoadPageTitles().
     */
    public function testLoadPageTitles()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadPagesByOwner
     * @todo   Implement testLoadPagesByOwner().
     */
    public function testLoadPagesByOwner()
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
     * @covers Wakka::FullTextSearch
     * @todo   Implement testFullTextSearch().
     */
    public function testFullTextSearch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::FullCategoryTextSearch
     * @todo   Implement testFullCategoryTextSearch().
     */
    public function testFullCategoryTextSearch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::CleanTextNode
     * @todo   Implement testCleanTextNode().
     */
    public function testCleanTextNode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::MakeMenu
     * @todo   Implement testMakeMenu().
     */
    public function testMakeMenu()
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
     * @covers Wakka::ParsePageTitle
     * @todo   Implement testParsePageTitle().
     */
    public function testParsePageTitle()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::HTTPpost
     * @todo   Implement testHTTPpost().
     */
    public function testHTTPpost()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::WikiPing
     * @todo   Implement testWikiPing().
     */
    public function testWikiPing()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetPingParams
     * @todo   Implement testGetPingParams().
     */
    public function testGetPingParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetSessionCookie
     * @todo   Implement testSetSessionCookie().
     */
    public function testSetSessionCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetPersistentCookie
     * @todo   Implement testSetPersistentCookie().
     */
    public function testSetPersistentCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::DeleteCookie
     * @todo   Implement testDeleteCookie().
     */
    public function testDeleteCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetCookie
     * @todo   Implement testGetCookie().
     */
    public function testGetCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SetRedirectMessage
     * @todo   Implement testSetRedirectMessage().
     */
    public function testSetRedirectMessage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetRedirectMessage
     * @todo   Implement testGetRedirectMessage().
     */
    public function testGetRedirectMessage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Redirect
     * @todo   Implement testRedirect().
     */
    public function testRedirect()
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
     * @covers Wakka::ListPages
     * @todo   Implement testListPages().
     */
    public function testListPages()
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
     * @covers Wakka::IsWikiName
     * @todo   Implement testIsWikiName().
     */
    public function testIsWikiName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::TrackLinkTo
     * @todo   Implement testTrackLinkTo().
     */
    public function testTrackLinkTo()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetLinkTable
     * @todo   Implement testGetLinkTable().
     */
    public function testGetLinkTable()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::ClearLinkTable
     * @todo   Implement testClearLinkTable().
     */
    public function testClearLinkTable()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::StartLinkTracking
     * @todo   Implement testStartLinkTracking().
     */
    public function testStartLinkTracking()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::StopLinkTracking
     * @todo   Implement testStopLinkTracking().
     */
    public function testStopLinkTracking()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::AddCustomHeader
     * @todo   Implement testAddCustomHeader().
     */
    public function testAddCustomHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Header
     * @todo   Implement testHeader().
     */
    public function testHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Footer
     * @todo   Implement testFooter().
     */
    public function testFooter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetThemePath
     * @todo   Implement testGetThemePath().
     */
    public function testGetThemePath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::SelectTheme
     * @todo   Implement testSelectTheme().
     */
    public function testSelectTheme()
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
     * @covers Wakka::ReadInterWikiConfig
     * @todo   Implement testReadInterWikiConfig().
     */
    public function testReadInterWikiConfig()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::AddInterWiki
     * @todo   Implement testAddInterWiki().
     */
    public function testAddInterWiki()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::GetInterWikiUrl
     * @todo   Implement testGetInterWikiUrl().
     */
    public function testGetInterWikiUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadReferrers
     * @todo   Implement testLoadReferrers().
     */
    public function testLoadReferrers()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Action
     * @todo   Implement testAction().
     */
    public function testAction()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Handler
     * @todo   Implement testHandler().
     */
    public function testHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::wrapHandlerError
     * @todo   Implement testWrapHandlerError().
     */
    public function testWrapHandlerError()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::existsHandler
     * @todo   Implement testExistsHandler().
     */
    public function testExistsHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::Format
     * @todo   Implement testFormat().
     */
    public function testFormat()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::authenticateUserFromCookies
     * @todo   Implement testAuthenticateUserFromCookies().
     */
    public function testAuthenticateUserFromCookies()
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
     * @covers Wakka::UserWantsComments
     * @todo   Implement testUserWantsComments().
     */
    public function testUserWantsComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::FormatUser
     * @todo   Implement testFormatUser().
     */
    public function testFormatUser()
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
     * @covers Wakka::LoadComments
     * @todo   Implement testLoadComments().
     */
    public function testLoadComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::loadCommentId
     * @todo   Implement testLoadCommentId().
     */
    public function testLoadCommentId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::TraverseComments
     * @todo   Implement testTraverseComments().
     */
    public function testTraverseComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::CountComments
     * @todo   Implement testCountComments().
     */
    public function testCountComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::CountAllComments
     * @todo   Implement testCountAllComments().
     */
    public function testCountAllComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadRecentComments
     * @todo   Implement testLoadRecentComments().
     */
    public function testLoadRecentComments()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::LoadRecentlyCommented
     * @todo   Implement testLoadRecentlyCommented().
     */
    public function testLoadRecentlyCommented()
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
     * @covers Wakka::LoadACL
     * @todo   Implement testLoadACL().
     */
    public function testLoadACL()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::TrimACLs
     * @todo   Implement testTrimACLs().
     */
    public function testTrimACLs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::isGroupMember
     * @todo   Implement testIsGroupMember().
     */
    public function testIsGroupMember()
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
     * @covers Wakka::readBadWords
     * @todo   Implement testReadBadWords().
     */
    public function testReadBadWords()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::writeBadWords
     * @todo   Implement testWriteBadWords().
     */
    public function testWriteBadWords()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::getBadWords
     * @todo   Implement testGetBadWords().
     */
    public function testGetBadWords()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Wakka::hasBadWords
     * @todo   Implement testHasBadWords().
     */
    public function testHasBadWords()
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

    /**
     * @covers Wakka::Run
     * @todo   Implement testRun().
     */
    public function testRun()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testInstantiation() {
        $this->assertInstanceOf('Wakka', self::$wakka);
    }
}