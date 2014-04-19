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
require_once('wikka/registry.php');
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

    /*
     * Static Methods
     */
     public static function load() {
        if ( isset($_SESSION['user']) ) {
            return self::find_by_name($_SESSION['user']['name']);
        }
        else {
            return self::unregistered_visitor();
        }
    }

    public static function find_by_name($name) {
        $sql_f = "SELECT * FROM %s WHERE name = ? LIMIT 1";
        $sql = sprintf($sql_f, parent::get_table());

        $pdo = WikkaRegistry::connect_to_db();
        $query = $pdo->prepare($sql);
        $query->execute(array($name));
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $user = new UserModel();

        if ( $result ) {
            $user->fields = $result;
            $user->fields['exists'] = TRUE;
        }
        else {
            $user->fields = array(
                'name' => $name,
                'exists' => FALSE
            );
        }

        return $user;
    }

    private static function unregistered_visitor() {
        $ip = $_SERVER['REMOTE_ADDR'];

        $user = new UserModel();
        $user->fields = array(
            'name' => ($ip) ? $ip : microtime(),
            'doubleclickedit' => FALSE,
            'exists' => FALSE
        );

        return $user;
    }

    /*
     * Public Instance Methods
     */
    public function can($action, $page) {
        $allow = 1;
        $deny = 0;
        $acl_key = sprintf('%s_acl', $action);
        $user_name = strtolower($this->fields['name']);

        if ( $this->is_admin() || $page->is_owned_by($this) ) {
            return TRUE;
        }

        $action_acl =$page->acl($acl_key);

        # ACLs are line-separated
        $acl_list = explode("\n", $action_acl);

        # Go line-by-line until you hit a matching rule
        foreach ($acl_list as $acl) {
            $acl = trim($acl);
            $negated = (int) (substr($acl, 0, 1) == '!');
            $acl = ( $negated ) ? trim(substr($acl, 1)) : $acl;

            # Skip empty lines and comments
            if ( (! $acl) || ($acl[0] == '#') ) {
                continue;
            }
            else {
                $rule = $acl[0];
            }

            # Apply rule
            if ( $rule == '*' ) {
                $access = $allow;
                return (boolean) ($negated ^ $access);
            }
            elseif ( $rule == '+' ) {
                $access = ( $this->is_logged_in() ) ? $allow : $deny;
                return (boolean) ($negated ^ $access);
            }
            elseif ( $user_name == strtolower($rule) ) {
                $access = $allow;
                return (boolean) ($negated ^ $access);
            }
            elseif ( $this->belongs_to_group($rule) ) {
                $access = $allow;
                return (boolean) ($negated ^ $access);
            }
            else {
                # Invalid rule? Just ignore.
                continue;
            }
        }

        return FALSE;
    }

    public function is_admin() {
        $admin_csv = $this->config['admin_users'];
        $admins = explode(',', $admin_csv);

        foreach ($admins as $admin) {
            if ( $this->fields['name'] == trim($admin) ) {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function is_logged_in() {
        return $this->exists();
    }

    public function belongs_to_group($page_tag) {
        #
        # For explanation, see http://www.wikkawiki.org/ACLsWithUserGroups
        #
        $group_page = PageModel::find_by_tag($page_tag);

        if ( $group_page->exists() ) {
            $needle = sprintf('+%s+', $this->field('name'));
            return strpos($group_page->field('body'), $needle) !== FALSE;
        }
        else {
            return FALSE;
        }
    }

    public function exists() {
        return $this->fields['exists'];
    }

    public function wants_comments_for_page($page) {
        $page_tag = $page->fields['tag'];

        if ( ! $this->exists() ) {
            return FALSE;
        }
        elseif ( isset($this->fields['show_comments'][$page_tag]) ) {
            return $this->fields['show_comments'][$page_tag];
        }
        elseif ( isset($this->fields['default_comment_display']) ) {
            return $this->fields['default_comment_display'];
        }
        else
        {
            if ( isset($this->config['default_comment_display']) ) {
                return $this->config['default_comment_display'];
            }
            else {
                return COMMENT_ORDER_DATE_ASC;  # system default
            }
        }
    }
}
