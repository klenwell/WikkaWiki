<?php
/**
 * libs/Wikka.class.php
 *
 * WikkaBlob subclasses the Wakka class so that the Wakka class can be
 * refactored without touching it. The goal is to eventually dismantle this
 * class and replace it with smaller focused modules.
 *
 * @package     Libs
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 *
 */
require_once('libs/Wakka.class.php');

 
class WikkaBlob extends Wakka {
    /*
     * Properties
     */


    /*
     * Constructor
     */
    public function __construct($config) {
        # Does not call Wakka parent constructor because I don't want it to
        # attempt a database connection yet. That should have to be established
        # explicitly by calling connect_to_db method.
        $this->config = $config;
        $this->VERSION = WAKKA_VERSION;
        $this->PATCH_LEVEL = WIKKA_PATCH_LEVEL;
    }
    
    /*
     * New Methods
     */
    public function connect_to_db() {
        # TODO: replace this with new style PDO class. All query related
        # methods will need to be updated.
        $host = $this->GetConfigValue('mysql_host');
        $user = $this->GetConfigValue('mysql_user');
        $pass = $this->GetConfigValue('mysql_password');
        $name = $this->GetConfigValue('mysql_database');
        
        $this->dblink = @mysql_connect($host, $user, $pass);
 
        if ($this->dblink) {
            mysql_query("SET NAMES 'utf8'", $this->dblink);
            
            if ( ! @mysql_select_db($name, $this->dblink) ){
                @mysql_close($this->dblink);
                $this->dblink = false;
            }
        }
        
        return $this->dblink;
    }
    
    /*
     * Overridden Methods
     */ 
}