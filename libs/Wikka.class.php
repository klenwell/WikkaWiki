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
    
    public function open_buffer() {
        # TODO: Replace buffering with properly designed templates.
        ob_start();
    }
    
    public function close_buffer() {
        # Closes the buffer and returns buffer content
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    public function save_session_to_db() {
        $user_name = $this->GetUser();
    
        # Only store sessions for logged in users
        if ( is_null($user_name) ) {
            return null;
        }
        
        $table_prefix = $this->config['table_prefix'];
        $session_id = session_id();
        
        # Look for current session record
        $query = sprintf('SELECT * FROM %ssessions WHERE sessionid="%s" AND userid="%s"',
            $table_prefix, $session_id, $user_name
        );
        
        $record = $this->LoadSingle($query);
        
        # Update session start time
        if ( $record ) {
            $query_f = <<<SQLDOC
UPDATE %ssessions
    SET session_start=FROM_UNIXTIME(%s)
    WHERE sessionid="%s" AND userid="%s"
SQLDOC;
    
            $query = sprintf($query,
                $table_prefix,
                $this->GetMicroTime(),
                $session_id,
                $user_name
            );
        }
        
        # Insert new session
        else {
            $query_f = <<<SQLDOC
INSERT INTO %ssessions (sessionid, userid, session_start)
    VALUES("%s", "%s", FROM_UNIXTIME(%s))
SQLDOC;
        
            $query = sprintf($query_f,
                $table_prefix,
                $session_id,
                $user_name,
                $this->GetMicroTime()
            );
        }
        
        # Write to db
        $this->Query($query);
        
        return $session_id;
    }
    
    function globalize_this_as_wakka_var() {
        # The formatter class requires a global $wakka var so we provide it
        # here. This is deliberately smelly in an effort to hasten its removal.
        global $wakka;
        $wakka = $this;
    }
    
    /*
     * Overridden Methods
     */ 
}