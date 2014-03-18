<?php

#
# Database Migrations
# Lists of SQL statements
#
$WikkaDatabaseMigrations = array(

    # version 0.1: list of MySQL statements
    '0.1' => array(
        "ALTER TABLE {{prefix}}pages ADD body_r TEXT NOT NULL DEFAULT '' AFTER body",
    ),
    
    '0.1.1' => array(),
    '0.1.2' => array(),
    '0.1.3-dev' => array(
        "ALTER TABLE {{prefix}}pages ADD note varchar(50) NOT NULL default '' after latest",
        "ALTER TABLE {{prefix}}pages DROP COLUMN body_r",
        "ALTER TABLE {{prefix}}users DROP COLUMN motto"
    ),
    
    '1.0'   => array(),
    '1.0.1' => array(),
    '1.0.2' => array(),
    '1.0.3' => array(),
    '1.0.4' => array(),
    '1.0.5' => array(),
    '1.0.6' => array(
        "CREATE TABLE {{prefix}}comments (" .
            "id int(10) unsigned NOT NULL auto_increment, " .
            "page_tag varchar(75) NOT NULL default '', " .
            "time datetime NOT NULL default '0000-00-00 00:00:00', " .
            "comment text NOT NULL, " .
            "user varchar(75) NOT NULL default '', " .
            "PRIMARY KEY (id), " .
			"KEY idx_page_tag (page_tag), " .
			"KEY idx_time (time)" .
            ") ENGINE={{engine}}",
        "INSERT INTO {{prefix}}comments (page_tag, time, comment, user) " .
            "SELECT comment_on, time, body, user FROM {{prefix}}pages " .
            "WHERE comment_on != ''",
        "DELETE FROM {{prefix}}pages WHERE comment_on != ''",
        "ALTER TABLE {{prefix}}pages DROP comment_on",
        "DELETE FROM {{prefix}}acls WHERE page_tag like 'Comment%'"
    ),
    
    '1.1.0' => array(
        "DROP TABLE {{prefix}}acls",
        "CREATE TABLE {{prefix}}acls (" .
			"page_tag varchar(75) NOT NULL default '', " .
			"read_acl text NOT NULL, " .
			"write_acl text NOT NULL, " .
			"comment_acl text NOT NULL, " .
			"PRIMARY KEY  (page_tag)" .
			") ENGINE={{engine}}",
    ),
    
    '1.1.2' => array(),
    '1.1.3' => array(
        "ALTER TABLE {{prefix}}pages CHANGE tag tag varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}pages CHANGE user user varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}pages CHANGE owner owner varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}pages CHANGE note note varchar(100) NOT NULL default ''",
        "ALTER TABLE {{prefix}}users CHANGE name name varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}comments CHANGE page_tag page_tag varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}comments CHANGE user user varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}acls CHANGE page_tag page_tag varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}links CHANGE from_tag from_tag varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}links CHANGE to_tag to_tag varchar(75) NOT NULL default ''",
        "ALTER TABLE {{prefix}}referrers MODIFY referrer varchar(150) NOT NULL default ''",
        "CREATE TABLE {{prefix}}referrer_blacklist (" .
			"spammer varchar(150) NOT NULL default '', " .
			"KEY idx_spammer (spammer)" .
			") ENGINE={{engine}}",
        "ALTER TABLE {{prefix}}pages DROP INDEX tag",
        "ALTER TABLE {{prefix}}pages ADD FULLTEXT body (body)",
        "ALTER TABLE {{prefix}}users DROP INDEX idx_name"
    ),
    
    '1.1.3.1' => array(),
    '1.1.3.2' => array(),
    '1.1.3.3' => array(),
    '1.1.3.4' => array(),
    '1.1.3.5' => array(),
    '1.1.3.6' => array(),
    '1.1.3.7' => array(),
    '1.1.3.8' => array(),
    '1.1.3.9' => array(),
    '1.1.4.0' => array(),
    '1.1.5.0' => array(),
    '1.1.5.1' => array(),
    '1.1.5.2' => array(),
    '1.1.5.3' => array(),
    '1.1.6.0' => array(),
    '1.1.6.1' => array(),
    '1.1.6.2-alpha' => array(),
    '1.1.6.2-beta' => array(),
    '1.1.6.2' => array(),
    '1.1.6.3' => array(
        "ALTER TABLE {{prefix}}users ADD COLUMN status enum (" .
            "'invited','signed-up','pending','active','suspended','banned','deleted')",
        "CREATE TABLE {{prefix}}sessions (" .
            "sessionid char(32) NOT NULL, " .
            "userid varchar(75) NOT NULL, " .
            "PRIMARY KEY (sessionid, userid), " .
            "session_start datetime NOT NULL)",
        "ALTER TABLE {{prefix}}links DROP INDEX `idx_from`"
    ),
    
    '1.1.6.4' => array(),
    '1.1.6.5' => array(),
    '1.1.6.6' => array(),
    '1.1.6.7' => array(),
);
# REPLACE {{prefix}} {{engine}}


#
# Functional Migrations
# These can invoke methods of the WikkaMigrator class
#
$WikkaFunctionalMigrations = array(
    '1.0.4' => array(
        # array(method_name, array(arg1, ...))
        array('add_config', array('double_doublequote_html', 'safe')),
    ),
    
    '1.1.3.2' => array(
        array('add_config', array('wikiping_server', '')),
    ),
    
    '1.1.5.3' => array(
        array('delete_cookie', array('name')),
        array('delete_cookie', array('password')),
        array('delete_path', array('actions/wakkabug.php')),
        array('delete_path', array('freemind')),
        array('delete_path', array('safehtml')),
        array('delete_path', array('wikiedit2')),
        array('delete_path', array('xml')),
    ),
    
    '1.1.6.1' => array(
        array('add_config', array('grabcode_button', '1')),
        array('add_config', array('wiki_suffix', '1')),
        array('add_config', array('require_edit_note', '1')),
        array('add_config', array('public_sysinfo', '0')),
        array('delete_cookie', array('wikka_user_name')),
        array('delete_cookie', array('wikka_pass')),
    ),
    
    '1.1.6.3' => array(
        array('add_config', array('allow_user_registration', '1')),
        array('add_config', array('wikka_template_path', 'templates')),
    ),
);
