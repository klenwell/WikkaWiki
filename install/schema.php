<?php
#
# Database Schema
# Configures database and creates tables
#
require_once('models/page.php');
require_once('models/user.php');
require_once('models/acl.php');
require_once('models/comment.php');



$WikkaDatabaseSchema = array(
	'character set' 	=> 'ALTER DATABASE {{db_name}} DEFAULT CHARACTER ' .
		'SET utf8 COLLATE utf8_unicode_ci',
	'pages table' 		=> PageModel::get_schema(),
	'users table' 		=> UserModel::get_schema(),
	'acls table' 		=> AccessControlListModel::get_schema(),
	'comments table'	=> CommentModel::get_schema(),
	'referrers table'	=> ReferrerModel::get_schema()
);

$WikkaDatabaseSchema['links table'] = <<<EOC
CREATE TABLE {{prefix}}links (
	from_tag varchar(75) NOT NULL default '',
	to_tag varchar(75) NOT NULL default '',
	UNIQUE KEY from_tag (from_tag,to_tag),
	KEY idx_to (to_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;

$WikkaDatabaseSchema['referrer_blacklist table'] = <<<EOC
CREATE TABLE {{prefix}}referrer_blacklist (
	spammer varchar(255) NOT NULL default '',
	KEY idx_spammer (spammer)
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

