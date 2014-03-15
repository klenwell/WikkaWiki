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
require_once('wikka/errors.php');

 
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
    
    public function normalize_handler_name($handler_name) {
        # If contains slashes, extract part after last slash. This is to avoid
        # possible directory traversal
        $has_slash = strpos($handler_name, '/') !== false;
        
        if ( $has_slash ) {
            $parts = explode('/', $handler_name);
            $handler_name = end($parts);
        }
        
        return strtolower($handler_name);
    }
    
    private function validate_handler($handler_name) {
        #
        # Throws WikkaHandlerError or returns true.
        #
        
        # Validate syntax
        $valid_handler_name_regex = '/^([a-z0-9_.-]+)$/';
        $handler_syntax_is_valid = preg_match($valid_handler_name_regex, $handler);
        if ( ! $handler_syntax_is_valid ) {
            throw new WikkaHandlerError(T_(
                "Unknown handler; the handler name must not contain special characters."
            ));
        }
        
        # Look for class first
        if ( $wikka->handler_class_exists($handler_name) ) {
            return true;
        }
        # Then look for legacy handler
        elseif ( $wikka->legacy_handler_exists($handler_name) ) {
            return true;
        }
        # Else raise error
        else {
            throw new WikkaHandlerError(
                sprintf(T_("Sorry, [%s] is an unknown handler."), $handler_name)
            );
        }
    }
    
    public function handler_class_exists($handler_name) {
        $handler_class_path = $this->build_handler_class_path($handler_name);
        return !(is_null($refactored_handler_path));
    }
    
    public function build_handler_class_path($handler_name) {
        $handler_fname = sprintf('%s.php', $handler_name);
        
        return $this->BuildFullpathFromMultipath(
            $handler_fname,
            $this->GetConfigValue('handler_path')
        );
    }
    
    public function legacy_handler_exists($handler_name) {
        $legacy_handler_path = $this->build_legacy_handler_path($handler_name);
        return !(is_null($legacy_handler_path));
    }
    
    public function build_legacy_handler_path($handler_name) {
        $handler_path = sprintf('%s%s%s.php', $handler, DIRECTORY_SEPARATOR, $handler);
        
        return $this->BuildFullpathFromMultipath(
            $handler_path,
            $this->GetConfigValue('handler_path')
        );
    }
    
    public function load_handler_class($handler_name) {
        $handler_path = $this->build_handler_class_path($handler_name);
        $HandlerClass = sprintf('%sHandler', ucwords($handler));
        
        require_once($handler_path);
        $handler = new $HandlerClass($this);
        return $handler;
    }
    
    /*
     * Private Methods
     */
    
    /*
     * Overridden Methods
     */
    #
    # Loads handler giving precedence to new-style handlers
    #
    public function __Handler($handler_name) {
        $handler_path = $this->build_legacy_handler_path($handler_name);
        
        return $this->IncludeBuffered($handler_path, '', '',
            $this->GetConfigValue('handler_path'));
    }
}