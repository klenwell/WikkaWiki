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
require_once('wikka/response.php');
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
        $user = $this->GetUser();
    
        # Only store sessions for logged in users
        if ( is_null($user) ) {
            return null;
        }
        
        $table_prefix = $this->config['table_prefix'];
        $session_id = session_id();
        $user_name = $user['name'];
        
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
    
    public function validate_handler($handler_name) {
        #
        # Throws WikkaHandlerError or returns true.
        #
        
        # Validate syntax
        $valid_handler_name_regex = '/^([a-z0-9_.-]+)$/';
        $handler_syntax_is_valid = preg_match($valid_handler_name_regex, $handler_name);
        if ( ! $handler_syntax_is_valid ) {
            throw new WikkaHandlerError(T_(
                "Unknown handler; the handler name must not contain special characters."
            ));
        }
        
        # Look for class first
        if ( $this->handler_class_exists($handler_name) ) {
            return true;
        }
        # Then look for legacy handler
        elseif ( $this->legacy_handler_exists($handler_name) ) {
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
        return !(is_null($handler_class_path));
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
        $handler_path = sprintf('%s%s%s.php', $handler_name,
            DIRECTORY_SEPARATOR, $handler_name);
        
        return $this->BuildFullpathFromMultipath(
            $handler_path,
            $this->GetConfigValue('handler_path')
        );
    }
    
    public function load_handler_class($handler_name) {
        $handler_path = $this->build_handler_class_path($handler_name);
        $HandlerClass = sprintf('%sHandler', ucwords($handler_name));
        
        require_once($handler_path);
        $handler = new $HandlerClass($this);
        return $handler;
    }
    
    public function format_error($content) {
        #
        # TODO(klenwell): Replace with a template or view
        #
        $template = <<<XHTML
    <div id="content">
        <div class="error">%s</div>
        <div style="clear: both"></div>
    </div>
XHTML;
        
        return sprintf($template, $content);
    }
    
    /*
     * Private Methods
     */
    private function run_legacy_handler($handler_name) {
        $handler_path = sprintf('%s%s%s.php', $handler_name,
            DIRECTORY_SEPARATOR, $handler_name);
        
        $content = $this->IncludeBuffered($handler_path, '', '',
            $this->GetConfigValue('handler_path'));
        
        if ( $content === false ) {
            $content = $this->wrapHandlerError(sprintf(
                T_("Sorry, [%s] is an unknown handler."), $handler_path
            ));
        }
        
        $response = new WikkaResponse($content);
        return $response;
    }
    
    /*
     * Overridden Methods
     */
    #
    # Loads page and calls appropriate handler. Return page content.
    #
    public function Run($page_name, $handler_name='') {
        #
        # Refactored to return a WikkaResponse object
        #
        
        # If no page name provided, redirect to root page
        if ( ! trim($page_name) ) {
            $this->Redirect($this->Href('', $this->GetConfigValue('root_page')));
        }
        
        # Set handler
        if ( trim($handler_name) ) {
            $this->handler = trim($handler_name);
        }
        else {
            $this->handler = 'show';
        }
        
        # Set default cookie path. (Why are we doing this here when we
        # already set a constant!?) It gets used in other methods.
        $this->wikka_cookie_path = WIKKA_COOKIE_PATH;
        
        # Set wikka_url
        $this->wikka_url = ((bool) $this->GetConfigValue('rewrite_mode')) ?
            WIKKA_BASE_URL : WIKKA_BASE_URL.WIKKA_URL_EXTENSION;
        $this->config['base_url'] = $this->wikka_url; # backward compatibility
        
        # Load user
        if ( $this->GetUser() ) {
            $this->registered = true;
        }
        else {
            # Are we really passing password in the cookie? WTF?
            # TODO(klenwell): fix this.
            $user = $this->LoadUser($this->GetCookie('user_name'),
                $this->GetCookie('pass'));
            
            if ( $user ) {
                $this->SetUser($user);
            }
            
            # Is this some terrible legacy code?
            if ( isset($_COOKIE['wikka_user_name']) ) {
                $user = $this->LoadUser($_COOKIE['wikka_user_name'],
                    $_COOKIE['wikka_pass']);
                
                # Delete old cookies and set user
                if ( $user ) {
                    SetCookie('wikka_user_name', '', 1, WIKKA_COOKIE_PATH);
                    $_COOKIE['wikka_user_name'] = '';
                    SetCookie('wikka_pass', '', 1, WIKKA_COOKIE_PATH);
                    $_COOKIE['wikka_pass'] = '';
                    $this->SetUser($user);
                }
            }
        }
        
        # Load page and ACLs
        $this->SetPage($this->LoadPage($page_name, $this->GetSafeVar('time', 'get'))); #312
        $this->ACLs = $this->LoadAllACLs($this->GetPageTag());
        
        # Log referrer and read interwiki config
        $this->LogReferrer();
        $this->ReadInterWikiConfig();
        
        # Time for some maintenance?
        if ( !($this->GetMicroTime() % 3) ) {
            $this->Maintenance();
        }
        
        # Various handler types
        if ( preg_match('/\.(xml|mm)$/', $this->GetHandler()) ) {
            $content = $this->handler($this->GetHandler());
            header('Content-Type: text/xml');
        }
        elseif ( $this->GetHandler() == "raw" ) {
            $content = $this->handler($this->GetHandler());
            header('Content-Type: text/plain');
        }
        elseif ( $this->GetHandler() == 'grabcode' ) {
            $content = $this->handler($this->GetHandler());
        }
        elseif ( $this->GetHandler() == 'html' ) {
            $content = $this->handler($this->GetHandler());
            header('Content-Type: text/html');
        }
        
        # If page name has spaces in it, replace spaces with _ and redirect to new
        # page name
        # TODO: normalize page name somewhere
        elseif( 0 !== strcmp($newtag = preg_replace('/\s+/', '_', $page_name),
            $page_name) ) {
            header("Location: ".$this->Href('', $newtag));
        }
        
        # These next two cases should not be necessary (and yet here they are)
        elseif ( preg_match('/\.(gif|jpg|png)$/', $this->GetHandler()) ) {
            header('Location: images/' . $this->GetHandler());
        }
        elseif ( preg_match('/\.css$/', $this->GetHandler()) ) {
            header('Location: css/' . $this->GetHandler());
        }
        
        # All the other handlers (including show)
        else {
            $handler_response = $this->handler($this->GetHandler());
            
            $content_items = array(
                $this->Header(),
                $handler_response->body,
                $this->Footer()
            );
            
            $content = implode("\n", $content_items);
        }
        
        $response = new WikkaResponse($content);
        $response->merge_response_headers($handler_response);
        return $response;
    }
    
    public function Handler($handler_name) {
        $handler_name = $this->normalize_handler_name($handler_name);
        
        $this->validate_handler($handler_name);
        
        if ( $this->handler_class_exists($handler_name) ) {
            $handler = $this->load_handler_class($handler_name);
            return $handler->handle();
        }
        else {
            return $this->run_legacy_handler($handler_name);
        }
    }
}