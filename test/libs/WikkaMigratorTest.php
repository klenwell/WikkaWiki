<?php
/**
 * WikkaMigratorTest.php
 * 
 * Unit tests for WikkaMigrator class
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/libs/WikkaMigratorTest
 * 
 */
require_once('wikka/constants.php');
require_once('version.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('libs/Wikka.class.php');
require_once('libs/install/migrator.php');


#
# Create a mock WikkaMigrator to override methods that may have unwanted
# side-effect (e.g. delete_path)
#
class MockWikkaMigrator extends WikkaMigrator {
    
    public function __construct($migrations_file, $config) {
        require($migrations_file);
        $this->database_migrations = $WikkaDatabaseMigrations;
        $this->command_migrations = $WikkaCommandMigrations;
        
        # Set directly
        $this->config = $config;
        
        $this->pdo = $this->connect_to_db();
    }
    
    public function delete_path($path) {
        return 'skipped in testing';
    }
}


class WikkaMigratorTest extends PHPUnit_Framework_TestCase {
    
    protected static $config;
    protected static $migrator;
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        $this->setUpMockServerEnvironment();
        $this->config = $this->setUpConfig();
        $this->pdo = $this->setUpDatabase();
        $this->setUpOldDatabaseSchema();
        
        $this->migrator = new MockWikkaMigrator('install/migrations.php',
            $this->config);
    }
    
    public function tearDown() {
        $_SERVER = array();
        $this->migrator = null;
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
    
    private function setUpOldDatabaseSchema() {
        global $WikkaSqlSchema;
        $this->pdo->exec($WikkaSqlSchema['1.0']);
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
    
    private function tearDownSession() {
        if ( session_id() ) {
            session_destroy();
            $_SESSION = array();
        }
    }
    
    
    /**
     * Tests
     */
    public function testCommandMigration() {
        # Set pre-migration state
        unset($this->migrator->config['double_doublequote_html']);
        
        # Run migrations
        $this->migrator->config['table_prefix'] = '';
        $this->migrator->run_migrations('1.0', '1.1.5.3');
        
        # Verify changes
        $log_messages = array_values($this->migrator->logs);
        $this->assertEquals(31, count($log_messages));
        $this->assertEquals('safe',
            $this->migrator->config['double_doublequote_html']);
        $this->assertContains('delete_path(xml)', end($log_messages));
        
    }
    
    public function testDatabaseMigration() {
        # Verify pre-migration state
        $result = $this->pdo->query('SELECT comment_on FROM pages');
        $this->assertEquals(20, $result->rowCount());
        
        # Run migrations
        $this->migrator->config['table_prefix'] = '';
        $this->migrator->run_migrations('1.0', '1.0.6');
        
        # Verify changes
        $log_messages = array_values($this->migrator->logs);
        $this->assertEquals(6, count($log_messages));
        $this->assertContains('DELETE FROM acls', end($log_messages));
        
        # comment_on column should have been removed
        $this->setExpectedException('PDOException');
        $this->pdo->query('SELECT comment_on FROM pages');
    }
    
    public function testInstantiates() {
        $result = $this->pdo->query('SELECT * FROM pages');
        $this->assertInstanceOf('WikkaMigrator', $this->migrator);
        $this->assertEquals('wikkawiki_test', $this->config['mysql_database']);
        $this->assertEquals(20, $result->rowCount());
    }
}


#
# Old version of Wikka Schema for testing migrations
#
$WikkaSqlSchema = array();
$WikkaSqlSchema['1.0'] = <<<ENDSQL

CREATE TABLE IF NOT EXISTS `acls` (
  `page_tag` varchar(50) NOT NULL DEFAULT '',
  `privilege` varchar(20) NOT NULL DEFAULT '',
  `list` text NOT NULL,
  PRIMARY KEY (`page_tag`,`privilege`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `links`
--

CREATE TABLE IF NOT EXISTS `links` (
  `from_tag` char(50) NOT NULL DEFAULT '',
  `to_tag` char(50) NOT NULL DEFAULT '',
  UNIQUE KEY `from_tag` (`from_tag`,`to_tag`),
  KEY `idx_from` (`from_tag`),
  KEY `idx_to` (`to_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(50) NOT NULL DEFAULT '',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `body` mediumtext NOT NULL,
  `owner` varchar(50) NOT NULL DEFAULT '',
  `user` varchar(50) NOT NULL DEFAULT '',
  `latest` enum('Y','N') NOT NULL DEFAULT 'N',
  `note` varchar(50) NOT NULL DEFAULT '',
  `handler` varchar(30) NOT NULL DEFAULT 'page',
  `comment_on` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_tag` (`tag`),
  KEY `idx_time` (`time`),
  KEY `idx_latest` (`latest`),
  KEY `idx_comment_on` (`comment_on`),
  FULLTEXT KEY `tag` (`tag`,`body`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `tag`, `time`, `body`, `owner`, `user`, `latest`, `note`, `handler`, `comment_on`) VALUES
(1, 'HomePage', '2014-03-17 20:22:55', 'Welcome to your Wakka site! Click on the "Edit page" link at the bottom to get started.\n\nAlso don''t forget to visit [[WakkaWiki:WakkaWiki WakkaWiki]]!\n\nUseful pages: FormattingRules, OrphanedPages, WantedPages, TextSearch.', 'KlenwellAdmin', 'KlenwellAdmin', 'Y', '', 'page', ''),
(2, 'RecentChanges', '2014-03-17 20:22:55', '{{RecentChanges}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(3, 'RecentlyCommented', '2014-03-17 20:22:55', '{{RecentlyCommented}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(4, 'UserSettings', '2014-03-17 20:22:55', '{{UserSettings}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(5, 'PageIndex', '2014-03-17 20:22:55', '{{PageIndex}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(6, 'WantedPages', '2014-03-17 20:22:55', '{{WantedPages}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(7, 'OrphanedPages', '2014-03-17 20:22:55', '====Orphaned Pages====\n\nThe following list shows those pages held in the Wiki that are not linked to on any other pages.\n\n{{OrphanedPages}}21232f297a57a5a743894a0e4a801fc3{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(8, 'TextSearch', '2014-03-17 20:22:55', '{{TextSearch}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(9, 'TextSearchExpanded', '2014-03-17 20:22:55', '{{textsearchexpanded}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(10, 'MyPages', '2014-03-17 20:22:55', '{{MyPages}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(11, 'MyChanges', '2014-03-17 20:22:55', '{{MyChanges}}{{nocomments}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(12, 'InterWiki', '2014-03-17 20:22:55', '{{interwikilist}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(13, 'PasswordForgotten', '2014-03-17 20:22:55', '{{emailpassword}}\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(14, 'WikiCategory', '2014-03-17 20:22:55', '===This wiki is using a very flexible but simple categorizing system to keep everything properly organized.===\n\n{{Category page="/"  col="10"}}\n==Here''s how it works :==\n~- The master list of the categories is **Category Category** (//without the space//) which will automatically list all known maincategories, and should never be edited. This list is easily accessed from the Wiki''s top navigation bar. (Categories).\n~- Each category has a WikiName name of the form ""CategoryName"" for example CategoryWiki etc. (see list of maincategories above)\n~- Pages can belong to zero or more categories. Including a page in a category is done by simply mentioning the ""CategoryName"" on the page (by convention at the very end of the page).\n~- The system allows to build hierarchies of categories by referring to the parent category in the subcategory page. The parent category page will then automatically include the subcategory page in its list.\n~- A special kind of category is **""Category Users""** (//without the space//) to group the userpages, so your Wiki homepage should include it at the end to be included in the category-driven userlist.\n~- New categories can be created (think very hard before doing this though, we don''t need too much of them) by creating a ""CategoryName"" page, including ""{{Category}}"" in it and placing it in the **Category Category** (//without the space//) category (for a main category or another parent category in case you want to create a subcategory).\n\n**Please help to keep this place organized by including the relevant categories in new and existing pages !**\n\n**Notes:** \n~- The above bold items above //include spaces// to prevent this page from showing up in the mentioned categories. This page only belongs in CategoryWiki (which can be safely mentioned) after all !\n~- In order to avoid accidental miscategorization you should **avoid** mentioning a non-related ""CategoryName"" on a page. This is a side-effect of how the categorizing system works: it''s based on a textsearch and is not restricted to the footer convention.\n~- Don''t be put of by the name of this page (WikiCategory) which is a logical name (it''s about the Wiki and explains Category) but doesn''t have any special role in the Categorizing system.\n~- To end with this is the **standard convention** to include the categories (both the wiki code and the result):\n\n%%==Categories==\nCategoryWiki%%\n\n==Categories==\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(15, 'CategoryWiki', '2014-03-17 20:22:55', '======Wiki Related Category======\nThis Category will contain links to pages talking about Wikis and Wikis specific topics. When creating such pages, be sure to include CategoryWiki at the bottom of each page, so that page shows listed.\n\n\n----\n\n{{Category col="3"}}\n\n\n----\n[[CategoryCategory List of all categories]]', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(16, 'CategoryCategory', '2014-03-17 20:22:55', '======List of All Categories======\nBelow is the list of all Categories existing on this Wiki, granted that users did things right when they created their pages or new Categories. See WikiCategory for how the system works.\n\n----\n\n{{Category}}', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(17, 'FormattingRules', '2014-03-17 20:22:55', '==== The Wiki Formatting Guide ====\n\nAnything between 2 sets of double-quotes is ignored and presented exactly as typed (that means the formatting commands below are ignored whenever surrounded by double double-quotes.)\n\nOnce you''ve read through this, test your formatting skills in the SandBox.\n----\n===Basic formatting:===\n\n	""**I''m bold text!**""\n	**I''m bold text!**\n\n	""//I''m italic text!//""\n	//I''m italic text!//\n\n	""And I''m __underlined__!""\n	And I''m __underlined__!\n\n	""##monospace text##""\n	##monospace text##\n\n	""''''highlight text''''"" (using 2 single-quotes)\n	''''highlight text''''\n\n	""++Strike through text++""\n	++Strike through text++\n\n	""Press #%ANY KEY#%""\n	Press #%ANY KEY#%\n\n	""@@Center text@@""\n	@@Center text@@\n\n ===Headers:===\n	""====== Really big header ======""\n	====== Really big header ======\n	\n	""===== Rather big header =====""\n	===== Rather big header =====\n	\n	""==== Medium header ===="" \n	==== Medium header ====\n	\n	""=== Not-so-big header ==="" \n	=== Not-so-big header ===\n	\n	""== Smallish header =="" \n	== Smallish header ==\n\n===Horizontal separator:===\n	""----""\n----\n\n===Forced line break:===\n	""---""\n---\n----\n===Lists / Indents:===\nIndent text using **4** spaces (which will auto-convert into tabs) or using "~". To make bulleted / ordered lists, use the following codes (you can use 4 spaces instead of "~"):\n\n""~- bulleted list:""\n	- bulleted list\n	- Line two\n\n""~1) numbered list:""\n	1) numbered list\n	1) Line two\n\n""~A) Using uppercase characters:""\n	A) Using uppercase characters\n	A) Line two\n\n""~a) Using lowercase characters:""\n	a) Using lowercase characters\n	a) Line two\n\n""~I) using uppercase roman numerals:""\n	I) using Latin numbers\n	I) Line two\n\n""~i) using lowercase roman numerals:""\n	i) using Latin numbers\n	i) Line two\n\n----\n===Wiki Extensions:===\n\n==Images:==\n\nTo place images on a Wiki page, use:\n""{{image class="center" alt="DVD logo" title="An Image Link" url="images/dvdvideo.gif" link="RecentChanges"}}""\n{{image class="center" alt="dvd logo" title="An Image Link" url="images/dvdvideo.gif" link="RecentChanges"}}\nLinks can be external, or internal Wiki links. You don''t have to enter a link at all, and in that case just an image will be inserted. You can also use the classes ''left'' and ''right'' to float images left and right. You don''t need to use all those attributes, only url is essential.\n\n==Tables:==\n\nTo create a table use this code:\n""{{table columns="3" cellpadding="1" cells="BIG;GREEN;FROGS;yes;yes;no;no;no;###"}}"" to give:\n\n{{table columns="3" cellpadding="1" cells="BIG;GREEN;FROGS;yes;yes;no;no;no;###"}}\n\n""###"" means the cell is empty.\n\n==Coloured Text:==\n\n""{{colour c="blue" text="This is a test."}}"" gives:\n\n{{colour c="blue" text="This is a test."}}\n\nIf you want to use hex values:\n\n""{{colour hex="#DD0000" text="This is another test."}}"" to give:\n\n{{colour hex="#DD0000" text="This is another test."}}\n	\n\n----\n\n **Left floated box - use two < signs before and after the block**\n	<<Some text in a floated box hanging around<<Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler.\n\n	**Right floated box, use two > characters before and after the block**\n	>>Some text in a floated box hanging around>>Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler. Some more text as a filler.\n\n	""Use ::c::  to clear floated blocks...""\n\n----\n===Code Formatters:===\n\n""%%code%%:""\n%%\nint main(int arc,char **argv)\n{\n	printf("Hello, %s!\n", (argc>1) ? argv[1] : "World");\n	return 0;\n}\n%%\n\n	""%%(ini) INI file contents%%:""\n%%(ini)\n; Menu specification file for Opera 7.0\n\n[Version]\nFile Version=2\n\n[Info]  #background info\nName=Munin++ Menu\nDescription=Munin++ Menu\nAuthor=NonTroppo (originally by Rijk van Geijtenbeek)\nVersion=1.9\n%%\n	""%%(php) PHP code%%:""\n%%(php) \n<?php\nphpinfo();\n = "Hello, World!\n";\nprint "";?>\n%%\n\n	""%%(email) Email message%%:"" \n%%(email) \nHi!\n>>>> My Llama loves foot massage.\n>>> You really think so?\n>> Yes, I know he does.\n>Are you sure?\n\nOf course, yes!\n\nMr. Scruff\n%%\n\n----\n===Forced links:===\n	""[[http://wikka.jsnx.com]]""\n	[[http://wikka.jsnx.com]]\n\n	""[[http://wikka.jsnx.com My Wiki Site]]""\n	[[http://wikka.jsnx.com My Wiki Site]]\n\n\n----\n\n===Inter Wiki Links:===\n	See the InterWiki page for a full list of available engines. Here are some examples:\n\n	WikiPedia:Perception\n	CssRef:overflow\n	Google:CSS\n	Thesaurus:Dilate\n	Dictionary:Dream\n\n----\n\n===FAQ:===\n//Question: How do you un-WikiName a word ?//\nAnswer: Add two pair of double-quotes around the word: ""WikiName""\n\n//Question: How do you get a pair of double-quotes (without any text between them) to display properly ?//\nAnswer: Use the entity literal ##&amp;quot;## - ##&amp;quot&amp;quot;##\n\n//Question: How does Wakka Wiki know to what URL to send a visitor to if it wasn''t specified ?//\nAnswer: The link is to a forced WikiPage. That means a link to a page in this wiki is generated.\n\n//Question: So why does ""[[LALA_LELE]]"" send me to http://LALA_LELE ?//\nAnswer: The underscore breaks things. ""[[LALALELE]]"" doesn''t have this problem.\n\n\n==Back Links==\n{{backlinks}}\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(18, 'HighScores', '2014-03-17 20:22:55', '**Rankings based on quanity of OwnedPages*:**\n {{HighScores}}{{nocomments}}*//not quality.//\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(19, 'OwnedPages', '2014-03-17 20:22:55', '{{ownedpages}}{{nocomments}}These numbers merely reflect how many pages you have created, not how much content you have contributed or the quality of your contributions. To see how you rank with other members, you may be interested in checking out the HighScores. \n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', ''),
(20, 'SandBox', '2014-03-17 20:22:55', 'Test your formatting skills here.\n\n\n\n\n----\nCategoryWiki', '', 'KlenwellAdmin', 'Y', '', 'page', '');

-- --------------------------------------------------------

--
-- Table structure for table `referrers`
--

CREATE TABLE IF NOT EXISTS `referrers` (
  `page_tag` char(50) NOT NULL DEFAULT '',
  `referrer` char(150) NOT NULL DEFAULT '',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `idx_page_tag` (`page_tag`),
  KEY `idx_time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `name` varchar(80) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `revisioncount` int(10) unsigned NOT NULL DEFAULT '20',
  `changescount` int(10) unsigned NOT NULL DEFAULT '50',
  `doubleclickedit` enum('Y','N') NOT NULL DEFAULT 'Y',
  `signuptime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `show_comments` enum('Y','N') NOT NULL DEFAULT 'N',
  `show_spaces` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`name`),
  KEY `idx_name` (`name`),
  KEY `idx_signuptime` (`signuptime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`name`, `password`, `email`, `revisioncount`, `changescount`, `doubleclickedit`, `signuptime`, `show_comments`, `show_spaces`) VALUES
('KlenwellAdmin', '21232f297a57a5a743894a0e4a801fc3', 'klenwell@gmail.com', 20, 50, 'Y', '2014-03-17 20:22:55', 'N', 'N');

ENDSQL;
