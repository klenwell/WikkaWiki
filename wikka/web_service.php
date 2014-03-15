<?php
/**
 * web_service.php
 *
 * The WikkaWebService class orchestrates the response to each request. It
 * does so by build a request object, routing the request to the appropriate
 * handler, and then returning a response object that sends the response
 * back to the browser.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *  
 */
require_once('wikka/request.php');
require_once('wikka/response.php');
require_once('libs/Wikka.class.php');



class WikkaWebService {
    
    /*
     * Properties
     */
    public $config = array();
    public $pdo = null;

    /*
     * Constructor
     */
    public function __construct($config_file_path=null) {
        if ( ! $config_file_path ) {
           $config_file_path = WIKKA_CONFIG_PATH;
        }
        
        if ( version_compare(phpversion(),'5.3','<') ) {
            error_reporting(E_ALL);
        }
        else {
            error_reporting(WIKKA_ERROR_LEVEL);
        }
        
        $this->verify_requirements();
        $this->config = $this->load_config($config_file_path);
        $this->pdo = $this->connect_to_db();
    }
    
    /*
     * Public Methods
     */
    public function disable_magic_quotes_if_enabled() {
        # Magic quotes are now disabled by default in .htaccess file. But
        # just in case they're somehow still on, this workaround is provided.
        # For additional information, see:
        #   http://www.php.net/manual/en/security.magicquotes.disabling.php
        #
        # Returns boolean $magic_quotes_are_enabled. Magic quotes should be
        # disabled in any case by the time this function returns.
        $magic_quotes_are_enabled = get_magic_quotes_gpc();
        
        if ( $magic_quotes_are_enabled ) {
            $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
            while (list($key, $val) = each($process)) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][stripslashes($k)] = $v;
                        $process[] = &$process[$key][stripslashes($k)];
                    } else {
                        $process[$key][stripslashes($k)] = stripslashes($v);
                    }
                }
            }
            unset($process);
        }
        
        return (bool) $magic_quotes_are_enabled;
    }
    
    public function prepare_request() {
        $request = new WikkaRequest();
        $request->authenticate_csrf_token();
        $request->define_constants();
        return $request;
    }
    
    public function start_session() {
        session_set_cookie_params(0, WIKKA_COOKIE_PATH);
        session_name(md5(BASIC_COOKIE_NAME . $this->config['wiki_suffix']));
        session_start();
    }
    
    public function set_csrf_token() {
        # return token
        if ( ! isset($_SESSION['CSRFToken']) ) {
            $_SESSION['CSRFToken'] = sha1(getmicrotime());
        }
        return $_SESSION['CSRFToken'];
    }
    
    public function process_request($request) {
        $route = $this->route_request($request);
        $content = $this->run_wikka_handler($route['page'], $route['handler']);

        # Prepare response
        $response = new WikkaResponse($content, 200);
        $response->set_header('Content-Type', 'text/html; charset=utf-8');
        $response->set_header('Cache-Control', 'no-cache');
        $response->set_header('ETag', md5($content));
        $response->set_header('Content-Length:', strlen($content));
        
        # Set header for any headers set by Wikka handler
        $headers = headers_list();
        foreach ($headers as $header) {
            list($key, $value) = explode(':', $header, 2);
            $response->set_header($key, $value);
        }
        
        return $response;
    }
    
    public function process_error($error) {
        $content = $error->getMessage();
        $response = new WikkaResponse($content, 500);
        return $response;
    }
    
    public function route_request($request) {
        # Return associative array with page/handler values. This could be a
        # private function, but I'd prefer to unit test it.
        $page = null;
        $handler = null;
        
        # Get wakka param (strip first slash)
        $wakka_param = $request->get_param('wakka', '');
        $wakka_param = preg_replace("/^\//", "", $wakka_param);
        
        # Extract pagename and handler from URL
        # Note this splits at the FIRST / so $handler may contain one or more
        # slashes; This is not allowed, and ultimately handled in the Handler()
        # method. [SEC]
        $matches = array();
        if ( preg_match("#^(.+?)/(.*)$#", $wakka_param, $matches) ) {
            list(, $page, $handler) = $matches;
        }
        elseif ( preg_match("#^(.*)$#", $wakka_param, $matches) ) {
            list(, $page) = $matches;
        }
        
        # Fix lowercase mod_rewrite bug: URL rewriting makes pagename lowercase. #135
        if ( (isset($_SERVER['REQUEST_URI'])) && (strtolower($page) == $page) ) {
            $pattern = preg_quote($page, '/');
            $decoded_uri = urldecode($_SERVER['REQUEST_URI']);
            $match_url = array();
            
            if ( preg_match("/($pattern)/i", $decoded_uri, $match_url) ) {
                $page = $match_url[1];
            }
        }
        
        return array('page' => $page, 'handler' => $handler);
    }
    
    /*
     * Private Methods
     */
    private function run_wikka_handler($page_name, $handler_name) {
        $wikka = new WikkaBlob($this->config);
        $wikka->globalize_this_as_wakka_var();
        $wikka->open_buffer();
        $wikka->connect_to_db();
        $wikka->save_session_to_db();
        $wikka->Run($page_name, $handler_name);
        $content = $wikka->close_buffer();
        return $content;
    }
    
    private function verify_requirements() {
        if ( ! function_exists('version_compare') ||
            version_compare(phpversion(),MINIMUM_PHP_VERSION,'<') ) {
            $message = sprintf(ERROR_WRONG_PHP_VERSION, MINIMUM_PHP_VERSION);
            throw new WikkaWebServiceError($message);
        }
        
        if ( ! function_exists('mysql_connect') ) {
            throw new WikkaWebServiceError(ERROR_MYSQL_SUPPORT_MISSING);
        }
    }
    
    private function load_config($config_file_path) {
        # Load config settings
        require(WIKKA_DEFAULT_CONFIG_PATH);
        include($config_file_path);
        $wakkaConfig = array_merge($wakkaDefaultConfig, $wakkaConfig);
        
        # Load language defaults
        require_once('wikka/language_defaults.php');
        
        # Load multi-config
        if ( file_exists(WIKKA_MULTI_CONFIG_PATH) ) {
            require_once(WIKKA_MULTI_CONFIG_PATH);
        }
        
        return $wakkaConfig;
    }
    
    private function connect_to_db() {
        $host = $this->config['mysql_host'];
        $name = $this->config['mysql_database'];
        $user = $this->config['mysql_user'];
        $pass = $this->config['mysql_password'];
        $dsn = sprintf('mysql:host=%s;dbname=%s', $host, $name);
        
        $pdo = new PDO($dsn, $user, $pass);
        return $pdo;
    }
}
