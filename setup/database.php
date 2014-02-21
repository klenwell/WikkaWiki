<?php
/**
 * Database Setup
 * 
 * This is isolated here so that it can be used by both install.php and tests to
 * setup the database.
 * 
 * @package	Setup
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @filesource
 * 
 * @author {@link https://github.com/klenwell Tom Atwell}
 */

 /*
  * Heredocs don't work well in assoc array (see
  * http://stackoverflow.com/questions/11067993/), so define query format
  * strings here first.
  */
$create_pages_table_f = <<<EOC
CREATE TABLE %spages (
	id int(10) unsigned NOT NULL auto_increment,
	tag varchar(75) NOT NULL default '',
	title varchar(75) NOT NULL default '',
	time datetime NOT NULL default '0000-00-00 00:00:00',
	body mediumtext NOT NULL,
	owner varchar(75) NOT NULL default '',
	user varchar(75) NOT NULL default '',
	latest enum('Y','N') NOT NULL default 'N',
	note varchar(100) NOT NULL default '',
	PRIMARY KEY  (id),
	KEY idx_tag (tag),
	FULLTEXT KEY body (body),
	KEY idx_time (time),
	KEY idx_owner (owner), 
	KEY idx_latest (latest)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_acls_table_f = <<<EOC
CREATE TABLE %sacls (
	page_tag varchar(75) NOT NULL default '',
	read_acl text NOT NULL,
	write_acl text NOT NULL,
	comment_read_acl text NOT NULL,
	comment_post_acl text NOT NULL,
	PRIMARY KEY  (page_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_links_table_f = <<<EOC
CREATE TABLE %slinks (
	from_tag varchar(75) NOT NULL default '',
	to_tag varchar(75) NOT NULL default '',
	UNIQUE KEY from_tag (from_tag,to_tag),
	KEY idx_to (to_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_referrers_table_f = <<<EOC
CREATE TABLE %sreferrers (
	page_tag varchar(75) NOT NULL default '',
	referrer varchar(255) NOT NULL default '',
	time datetime NOT NULL default '0000-00-00 00:00:00',
	KEY idx_page_tag (page_tag),
	KEY idx_time (time)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_referrer_blacklist_table_f = <<<EOC
CREATE TABLE %sreferrer_blacklist (
	spammer varchar(255) NOT NULL default '',
	KEY idx_spammer (spammer)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_users_table_f = <<<EOC
CREATE TABLE %susers (
	name varchar(75) NOT NULL default '',
	password varchar(32) NOT NULL default '',
	email varchar(50) NOT NULL default '',
	revisioncount int(10) unsigned NOT NULL default '20',
	changescount int(10) unsigned NOT NULL default '50',
	doubleclickedit enum('Y','N') NOT NULL default 'Y',
	signuptime datetime NOT NULL default '0000-00-00 00:00:00',
	show_comments enum('Y','N') NOT NULL default 'N',
	status enum('invited','signed-up','pending','active','suspended','banned','deleted'),
	theme varchar(50) default '',
	default_comment_display enum ('date_asc', 'date_desc', 'threaded') NOT NULL default 'threaded',
	challenge varchar(8) default '',
	PRIMARY KEY  (name),
	KEY idx_signuptime (signuptime)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_comments_table_f = <<<EOC
CREATE TABLE %scomments (
	id int(10) unsigned NOT NULL auto_increment,
	page_tag varchar(75) NOT NULL default '',
	time datetime NOT NULL default '0000-00-00 00:00:00',
	comment text NOT NULL,
	user varchar(75) NOT NULL default '',
	parent int(10) unsigned default NULL,. 
	status enum('deleted') default NULL,
	deleted char(1) default NULL,
	PRIMARY KEY  (id),
	KEY idx_page_tag (page_tag),
	KEY idx_time (time)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;

$create_sessions_table_f = <<<EOC
CREATE TABLE %ssessions (
	sessionid char(32) NOT NULL,
	userid varchar(75) NOT NULL,
	PRIMARY KEY (sessionid, userid),
	session_start datetime NOT NULL
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=%s
EOC;


/*
 * Now package all queries into an array
 */
$mysql_engine = 'MyISAM';
 
$install_queries = array(
	'alter-db-charset' => sprintf(
		"ALTER DATABASE %s DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;",
		$config['mysql_database']),
	'create-pages-table' => sprintf($create_pages_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-acls-table' => sprintf($create_acls_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-links-table' => sprintf($create_links_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-referrers-table' => sprintf($create_referrers_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-referrer_blacklist-table' => sprintf($create_referrer_blacklist_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-users-table' => sprintf($create_users_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-comments-table' => sprintf($create_comments_table_f,
		$config['table_prefix'], $mysql_engine),
	'create-sessions-table' => sprintf($create_sessions_table_f,
		$config['table_prefix'], $mysql_engine),
);


