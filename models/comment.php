<?php
/**
 * models/comment.php
 *
 * WikkaWiki Comment model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');

 

class CommentModel extends WikkaModel {
    /*
     * Static Properties
     */
    protected static $schema = <<<MYSQL
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
MYSQL;
    
    protected static $table = 'comments';
    
}
