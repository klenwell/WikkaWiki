<?php
#
# Database Schema
# Configures database and creates tables
#
require_once('models/page.php');
require_once('models/user.php');
require_once('models/acl.php');



$WikkaDatabaseSchema = array(
	'character set' => 'ALTER DATABASE {{db_name}} DEFAULT CHARACTER ' .
		'SET utf8 COLLATE utf8_unicode_ci',
	'pages table' => PageModel::get_schema(),
	'users table' => UserModel::get_schema(),
	'acls table' => AccessControlListModel::get_schema()
);

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

