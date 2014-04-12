<?php
#
# Database Schema
# Configures database and creates tables
#
require_once('models/page.php');
require_once('models/user.php');
require_once('models/acl.php');
require_once('models/comment.php');
require_once('models/referrer.php');
require_once('models/session.php');
require_once('models/link.php');


$WikkaDatabaseSchema = array(
	'character set' 	=> 'ALTER DATABASE {{db_name}} DEFAULT CHARACTER ' .
		'SET utf8 COLLATE utf8_unicode_ci',
	'pages table' 		=> PageModel::get_schema(),
	'users table' 		=> UserModel::get_schema(),
	'acls table' 		=> AccessControlListModel::get_schema(),
	'comments table'	=> CommentModel::get_schema(),
	'referrers table'	=> ReferrerModel::get_schema(),
	'sessions table'	=> SessionModel::get_schema(),
	'links table'		=> LinkModel::get_schema()
);

$WikkaDatabaseSchema['referrer_blacklist table'] = <<<EOC
CREATE TABLE {{prefix}}referrer_blacklist (
	spammer varchar(255) NOT NULL default '',
	KEY idx_spammer (spammer)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
EOC;
