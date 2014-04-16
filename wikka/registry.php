<?php
/**
 * wikka/registry.php
 *
 * Uses singleton registry pattern to provide globals access to config
 * and database resources.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */



class WikkaRegistry {
    
    public static $config = null;
    private static $pdo = null;
    
    static public function init($config) {
        self::$config = $config;
    }
    
    /*
     * API
     */
    static public function get_config($key, $default=NULL) {
        self::validate();
        
        if ( isset(self::$config[$key]) ) {
            return self::$config[$key];
        }
        else {
            return $default;
        }
    }
    
    static public function connect_to_db() {
        self::validate();
        
        if ( ! is_null(self::$pdo) ) {
            return self::$pdo;
        }
        else {
            $dsn = sprintf('mysql:host=%s;dbname=%s',
                self::$config['mysql_host'],
                self::$config['mysql_database']
            );
            self::$pdo = new PDO($dsn,
                self::$config['mysql_user'],
                self::$config['mysql_password']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return self::$pdo;
        }
    }
    
    static public function disconnect_from_db() {
        self::$pdo = null;
    }
    
    /*
     * Private Methods
     */
    static private function validate() {
        if ( is_null(self::$config) ) {
            throw new Exception(
                'Config not set: have you called WikkaRegistry::init?');
        }
        else {
            return TRUE;
        }
    }
}
