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

class WikkaModel {
    
    /*
     * Table Schema
     * (This is just a sample and should be overridden in base class)
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}table (
	id int(10) unsigned NOT NULL auto_increment,
	column varchar(75) NOT NULL default '',
	PRIMARY KEY  (id),
	KEY idx_column (column),
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    /*
     * Properties
     */
    protected static $global_pdo = null;
    
    public $config = array();
    public $pdo = null;
    
    /*
     * Constructor
     */
    public function __construct($config) {
        $this->config = $config;
        $this->pdo = self::connect($config);
    }
    
    static private function connect($config) {
        if ( ! is_null(self::$global_pdo) ) {
            return self::$global_pdo;
        }
        else {
            $dsn = sprintf('mysql:host=%s;dbname=%s',
                $config['mysql_host'],
                $config['mysql_database']);
            self::$global_pdo = new PDO($dsn,
                $config['mysql_user'],
                $config['mysql_password']
            );
            self::$global_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return self::$global_pdo;
        }
    }
    
    /*
     * Public Methods
     */
    public function get_schema() {
        $schema = self::$schema;
        $vars = array();
        
        $replacement = array(
            'prefix' => $this->config['table_prefix'],
            'engine' => WIKKA_MYSQL_ENGINE
        );
        
        $matched = preg_match_all(BRACKET_VAR_REGEX, self::$schema, $vars);
        
        foreach ( $vars[0] as $var ) {
            $id = preg_replace('/[\{\}\s]/', '', $var);
            $schema = str_replace($var, $replacement[$id], $schema);
        }
        
        return $schema;
    }
}
