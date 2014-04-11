<?php
/**
 * models/acl.php
 *
 * WikkaWiki Access Control List model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');

 

class AccessControlListModel extends WikkaModel {
    /*
     * Static Properties
     * (These are just a sample and should be overridden in base class)
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}acls (
	page_tag varchar(75) NOT NULL default '',
	read_acl text NOT NULL,
	write_acl text NOT NULL,
	comment_read_acl text NOT NULL,
	comment_post_acl text NOT NULL,
	PRIMARY KEY  (page_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    protected static $table = 'acls';
    
}
