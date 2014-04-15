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
    
    public static function find_all($tag, $order=NULL) {
        
    }
    
    public static function find_by_page_tag($tag, $order=NULL) {
        $sql_f = <<<SQLF
SELECT * FROM %s
    WHERE page_tag = ?
    AND (status IS NULL or status != 'deleted')
    ORDER BY time %s
SQLF;

        if ( ! $order ) {
            if ( isset($_SESSION['show_comments'][$tag]) ) {
                $order = $_SESSION['show_comments'][$tag];
            }
            else {
                $order = COMMENT_ORDER_DATE_ASC;
            }
        }
        
        if ( $order == COMMENT_ORDER_THREADED ) {
            return self::find_by_page_tag_in_threaded_order($tag);
        }
        else {
            $order_by = ( $order == COMMENT_ORDER_DATE_DESC ) ? 'DESC' : 'ASC';
        }
        
        $sql = sprintf($sql_f, parent::get_table(), $order_by);
        
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($tag));
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        
        if ( ! $rows ) {
            return array();
        }
        
        $comments = array();
        foreach ( $rows as $row ) {
            $comment = new CommentModel();
            $comment->fields = $row;
            $comments[] = $comment;
        }

        return $comments;
    }
    
    public static function find_descendants_by_parent_id($id, $level=0) {
        $descendants = array();
        
        $sql_f = 'SELECT * FROM %s WHERE parent = ?';
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare(sprintf($sql_f, parent::get_table()));
        $query->execute(array($id));
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        
        if ( $rows ) {
            foreach ( $rows as $row ) {
                $child = $row;
                $child['level'] = $level;
                $descendants[] = $child;
                $descendants = array_merge($descendants,
                    self::find_descendants_by_parent_id($child['id'], $level+1)
                );
            }
        }
        
        return $descendants;
    }
    
    public static function find_by_page_tag_in_threaded_order($tag) {
        $adjacency_list = array();
        
        # find parents first
        $sql_f = 'SELECT * FROM %s WHERE page_tag = ? AND parent IS NULL';
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare(sprintf($sql_f, parent::get_table()));
        $query->execute(array($tag));
        $parents = $query->fetchAll(PDO::FETCH_ASSOC);

        # find descendants for each parents
        foreach ( $parents as $parent ) {
            $parent['level'] = 0;
            $adjacency_list[] = $parent;
            $children = self::find_descendants_by_parent_id($parent['id'], 1);
            $adjacency_list = array_merge($adjacency_list, $children);
        }
        
        return $adjacency_list;
    }
    
    public static function count_by_page_tag($tag) {
        $sql_f = <<<SQLF
SELECT COUNT(*) as count FROM %s
    WHERE page_tag = ?
    AND (status IS NULL or status != 'deleted')
SQLF;
        
        $sql = sprintf($sql_f, parent::get_table());
        
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($tag));
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        if ( ! $result ) {
            return 0;
        }
        else {
            return $result['count'];
        }
    }
    
}
