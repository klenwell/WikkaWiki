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
    
    /*
     * Static Methods
     */
    public static function load_defaults() {
        $config = WikkaResources::$config;
        $default_acls = array(
            'read_acl' => $config['default_read_acl'],
            'write_acl' => $config['default_write_acl'],
            'comment_read_acl' => $config['default_comment_read_acl'],
            'comment_post_acl' => $config['default_comment_post_acl']
        );
        return $default_acls;
    }
    
    public static function find_by_page_tag($tag) {
        $sql_f = "SELECT * FROM %s WHERE page_tag = ? LIMIT 1";
        $sql = sprintf($sql_f, parent::get_table());
        
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($tag));
        $row = $query->fetch(PDO::FETCH_ASSOC);
        
        if ( ! $row ) {
            $row = array('page_tag' => $tag);
        }
        
        $defaults = AccessControlListModel::load_defaults();
        $row = array_merge($defaults, $row);
        
        $acl = new AccessControlListModel();
        $acl->fields = $row;
        return $acl;
    }
    
}
