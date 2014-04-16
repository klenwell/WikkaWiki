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
require_once('wikka/registry.php');



class WikkaModel {
    
    /*
     * Static Properties
     * (These are just a sample and should be overridden in base class)
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}nonesuches (
	id int(10) unsigned NOT NULL auto_increment,
	nonce varchar(75) NOT NULL default '',
	PRIMARY KEY  (id),
	KEY idx_nonce (nonce)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    protected static $table = 'nonesuches'; # Don't include prefix. Will be added.
    
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
        $this->pdo = WikkaRegistry::connect_to_db();
        $this->config = WikkaRegistry::$config;
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
        $schema = static::$schema;
        $vars = array();
        
        $replacement = array(
            'prefix' => WikkaRegistry::get_config('table_prefix'),
            'engine' => WIKKA_MYSQL_ENGINE
        );
        
        $matched = preg_match_all(BRACKET_VAR_REGEX, self::$schema, $vars);
        
        foreach ( $vars[0] as $var ) {
            $id = preg_replace('/[\{\}\s]/', '', $var);
            $schema = str_replace($var, $replacement[$id], $schema);
        }
        
        return $schema;
    }
    
    static public function get_table() {
        return WikkaRegistry::get_config('table_prefix') . static::$table;
    }
    
    /*
     * Public Methods
     */
    public function field($name, $default=NULL) {
        if ( isset($this->fields[$name]) ) {
            return $this->fields[$name];
        }
        else {
            return $default;
        }
    }
    
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
        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', self::get_table(), $column);
        $query = $this->pdo->prepare($sql);
        return $query->execute(array($value));
    }
    
    public function find_by_id($id) {
        return $this->find_by_column_value('id', $id);
    }
}
