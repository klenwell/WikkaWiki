<?php
#
# Database Schema
# Configures database and creates tables
#
require_once('models/page.php');



$WikkaDatabaseSchema = array(
	'character set' => 'ALTER DATABASE {{db_name}} DEFAULT CHARACTER ' .
		'SET utf8 COLLATE utf8_unicode_ci',
	'pages table' => PageModel::$schema
);

$WikkaDatabaseSchema['acls table'] = <<<EOC
CREATE TABLE {{prefix}}acls (
	page_tag varchar(75) NOT NULL default '',
	read_acl text NOT NULL,
	write_acl text NOT NULL,
	comment_read_acl text NOT NULL,
	comment_post_acl text NOT NULL,
	PRIMARY KEY  (page_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['links table'] = <<<EOC
CREATE TABLE {{prefix}}links (
	from_tag varchar(75) NOT NULL default '',
	to_tag varchar(75) NOT NULL default '',
	UNIQUE KEY from_tag (from_tag,to_tag),
	KEY idx_to (to_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['referrers table'] = <<<EOC
CREATE TABLE {{prefix}}referrers (
	page_tag varchar(75) NOT NULL default '',
	referrer varchar(255) NOT NULL default '',
	time datetime NOT NULL default '0000-00-00 00:00:00',
	KEY idx_page_tag (page_tag),
	KEY idx_time (time)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['referrer_blacklist table'] = <<<EOC
CREATE TABLE {{prefix}}referrer_blacklist (
	spammer varchar(255) NOT NULL default '',
	KEY idx_spammer (spammer)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['users table'] = <<<EOC
CREATE TABLE {{prefix}}users (
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
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['comments table'] = <<<EOC
CREATE TABLE {{prefix}}comments (
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
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['sessions table'] = <<<EOC
CREATE TABLE {{prefix}}sessions (
	sessionid char(32) NOT NULL,
	userid varchar(75) NOT NULL,
	PRIMARY KEY (sessionid, userid),
	session_start datetime NOT NULL
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

