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
require_once('wikka/registry.php');
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
    public static function find_by_tag($tag) {
        $sql_f = "SELECT * FROM %s WHERE tag = ? AND latest = 'Y'";
        $sql = sprintf($sql_f, parent::get_table());

        $pdo = WikkaRegistry::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($tag));
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $page = new PageModel();

        if ( $result ) {
            $page->fields = $result;
        }
        else {
            $page->fields['tag'] = $tag;
        }

        return $page;
    }

    public static function find_by_tag_and_time($tag, $time) {
        $sql_f = "SELECT * FROM %s WHERE tag = ? AND time = ?";
        $sql = sprintf($sql_f, parent::get_table());

        $pdo = WikkaRegistry::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($tag, $time));
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $page = new PageModel();

        if ( $result ) {
            $page->fields = $result;
        }
        else {
            $page->fields['tag'] = $tag;
        }

        return $page;
    }

    /*
     * Instance Methods
     */
    public function save() {
        $sql_f = 'INSERT INTO %s (%s, time) VALUES (%s, NOW())';

        # Earlier versions are no longer latest
        $this->retire_earlier_versions();

        # This is the latest
        $this->fields['latest'] = 'Y';

        # Use database time for time
        if ( isset($this->fields['time']) ) {
            unset($this->fields['time']);
        }

        $sql = sprintf($sql_f,
            $this->get_table(),
            implode(', ', array_keys($this->fields)),
            implode(', ', array_fill(0, count($this->fields), '?'))
        );

        $query = $this->pdo->prepare($sql);
        $query->execute(array_values($this->fields));
        return $query;
    }

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

    /*
     * Private Methods
     */
    private function retire_earlier_versions() {
        $sql_f = "UPDATE %s SET latest = 'N' WHERE tag = ?";
        $sql = sprintf($sql_f, $this->get_table());

        $query = $this->pdo->prepare($sql);
        $query->execute(array($this->fields['tag']));
        return $query;
    }
}
