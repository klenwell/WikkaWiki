<?php
/**
 * models/user.php
 *
 * WikkaWiki User model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');

 

class UserModel extends WikkaModel {
    /*
     * Static Properties
     * (These are just a sample and should be overridden in base class)
     */
    protected static $schema = <<<MYSQL
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
MYSQL;
    
    protected static $table = 'users';
    
}
