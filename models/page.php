<?php
/**
 * models/page.php
 *
 * WikkaWiki Page model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');



class PageModel extends WikkaModel {
    /*
     * Static Properties
     * (These are just a sample and should be overridden in base class)
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}pages (
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
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    protected static $table = 'pages';
    
}
