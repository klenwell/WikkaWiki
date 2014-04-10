<?php
/**
 * models/base.php
 *
 * Base model class for WikkaWiki.
 *
 * Provides basic interface for models. Each model should be associated with
 * a table and a schema property holding the sql for the table. It also 
 * establishes a single re-usable PDO connection for all model instances.
 * 
 *
 * USAGE
 *  require_once('models/base.php');
 *
 *  class PageModel extends WikkaModel {
 *  }
 *  
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */

#
# WikkaResources
# Singleton registry pattern
# TODO: move into its own file
# 
class WikkaResources {
    
    public static $config = null;
    private static $pdo = null;
    
    static public function init($config) {
        self::$config = $config;
    }
    
    static public function connect_to_db() {
        if ( is_null(self::$config) ) {
            throw new Exception(
                'Config not set: have you called WikkaResources::init?');
        }
        
        if ( ! is_null(self::$pdo) ) {
            return self::$pdo;
        }
        else {
            $dsn = sprintf('mysql:host=%s;dbname=%s',
                self::$config['mysql_host'],
                self::$config['mysql_database']);
            self::$pdo = new PDO($dsn,
                self::$config['mysql_user'],
                self::$config['mysql_password']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return self::$pdo;
        }
    }
}


class WikkaModel {
    
    /*
     * Static Properties
     * (These are just a sample and should be overridden in base class)
     */
    protected static $table = 'nonesuches'; # Don't include prefix. Will be added.
    
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}nonesuches (
	id int(10) unsigned NOT NULL auto_increment,
	nonce varchar(75) NOT NULL default '',
	PRIMARY KEY  (id),
	KEY idx_nonce (nonce)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    /*
     * Properties
     */
    public $config = array();
    public $pdo = null;
    public $fields = array();
    
    /*
     * Constructor
     */
    public function __construct() {
        $this->pdo = WikkaResources::connect_to_db();
    }
    
    /*
     * Static Methods
     */
    static public function init($fields=array()) {
        $class = get_called_class();
        $instance = new $class();
        $instance->fields = $fields;
        return $instance;
    }
    
    static public function all() {
        $sql = sprintf('SELECT * FROM %s', $this->get_table());
        return $this->pdo->query($sql);
    }
    
    static public function get_schema() {
        $schema = self::$schema;
        $vars = array();
        
        $replacement = array(
            'prefix' => WikkaResources::$config['table_prefix'],
            'engine' => WIKKA_MYSQL_ENGINE
        );
        
        $matched = preg_match_all(BRACKET_VAR_REGEX, self::$schema, $vars);
        
        foreach ( $vars[0] as $var ) {
            $id = preg_replace('/[\{\}\s]/', '', $var);
            $schema = str_replace($var, $replacement[$id], $schema);
        }
        
        return $schema;
    }
    
    /*
     * Public Methods
     */
    public function save() {
        $sql_f = 'INSERT INTO %s (%s) VALUES (%s)';
        $sql = sprintf($sql_f,
            $this->get_table(),
            implode(', ', array_keys($this->fields)),
            implode(', ', array_fill(0, count($this->fields), '?'))
        );
        
        $query = $this->pdo->prepare($sql);
        $query->execute(array_values($this->fields));
        return $query;
    }
    
    public function find_by_column_value($column, $value) {
        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', $this->get_table(), $column);
        $query = $this->pdo->prepare($sql);
        return $query->execute(array($value));
    }
    
    public function find_by_id($id) {
        return $this->find_by_column_value('id', $id);
    }
                         
    public function get_table() {
        return WikkaResources::$config['table_prefix'] . self::$table;
    }
}
