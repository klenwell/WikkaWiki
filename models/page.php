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
require_once('models/acl.php');



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
    
    /*
     * Static Methods
     */
    public static function find_by_name($name) {
        $sql_f = "SELECT * FROM %s WHERE latest = 'Y' AND tag = ?";
        $sql = sprintf($sql_f, parent::get_table());
        
        $pdo = WikkaResources::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($name));
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        $page = new PageModel();
        
        if ( $result ) {
            $page->fields = $result;
        }
        else {
            $page->fields['tag'] = $name;
        }
        
        return $page;
    }
    
    /*
     * Instance Methods
     */
    public function is_owned_by($user) {
        return $this->fields['owner'] == $user->fields['name'];
    }
    
    public function load_acls() {
        return AccessControlListModel::find_by_page_tag($this->fields['tag']);
    }
    
    public function exists() {
        return isset($this->fields['id']);
    }
    
    public function tag_is_valid() {
        $invalid = preg_match(RE_INVALID_WIKI_NAME,
            html_entity_decode($this->fields['tag']));
        return !($invalid);
    }
    
    public function pretty_page_tag() {
        return preg_replace('/_+/', ' ', $this->fields['tag']);
    }
    
    public function is_latest_version() {
         return $this->field('latest') == 'Y';
    }
}
