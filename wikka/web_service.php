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
    public $request = null;

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
        $magic_quotes_were_enabled = get_magic_quotes_gpc();
        
        if ( $magic_quotes_were_enabled ) {
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
        
        return (bool) $magic_quotes_were_enabled;
    }
    
    public function prepare_request() {
        $request = new WikkaRequest();
        $request->define_constants();
        $this->request = $request;
        return $request;
    }
    
    public function start_session() {
        session_set_cookie_params(0, WIKKA_COOKIE_PATH);
        session_name(md5(BASIC_COOKIE_NAME . $this->config['wiki_suffix']));
        session_start();
        return null;
    }
    
    public function enforce_csrf_token() {
        $token = $this->set_csrf_token_if_not_set();
        $this->authenticate_csrf_token();
        return $token;
    }
    
    public function process_request() {
        $route = $this->route_request();
        $response = $this->run_wikka_handler($route['page'], $route['handler']);
        
        # Set common headers
        $response->set_header('Cache-Control', 'no-cache');
        $response->set_header('ETag', md5($response->body));
        $response->set_header('Content-Length', strlen($response->body));
        
        return $response;
    }
    
    public function process_error($error) {
        $route = $this->route_request();

        $wikka = new WikkaBlob($this->config);
        $wikka->connect_to_db();
        $wikka->handler = $route['handler'];
        $wikka->SetPage($wikka->LoadPage($route['page']));
        
        $content_items = array(
            $wikka->Header(),
            $wikka->format_error($error->getMessage()),
            $wikka->Footer()
        );
        
        $content = implode("\n", $content_items);

        $response = new WikkaResponse($content, 500);
        $response->set_header('Cache-Control', 'no-cache');
        $response->set_header('ETag', md5($response->body));
        $response->set_header('Content-Length', strlen($response->body));
        
        return $response;
    }
    
    public function process_installer() {
        $wikka = new WikkaBlob($this->config);
        $wikka->connect_to_db();
    
        $install_handler = $wikka->load_handler_class('install');
        $response = $install_handler->handle($this);
        
        return $response;
    }
    
    public function route_request() {
        # Return associative array with page/handler values. This could be a
        # private function, but I'd prefer to unit test it.
        $page = null;
        $handler = null;
        
        # Get wakka param (strip first slash)
        $wakka_param = $this->request->get_param('wakka', '');
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
    
    public function interrupt_if_install_required() {
        if ( $this->config['wakka_version'] !== WAKKA_VERSION ) {
            if ( ! $this->config['wakka_version'] ) {
                $m = "Install required";
            }
            else {
                $m = sprintf("Upgrade required: version %s to %s",
                    $this->config['wakka_version'], WAKKA_VERSION);
            }
            
            throw new WikkaInstallInterrupt($m);
        }
        else {
            return null;
        }
    }
    
    /*
     * Private Methods
     */
    private function run_wikka_handler($page_name, $handler_name) {
        $wikka = new WikkaBlob($this->config);
        $wikka->globalize_this_as_wakka_var();
        $wikka->connect_to_db();
        $wikka->save_session_to_db();
        $response = $wikka->Run($page_name, $handler_name);
        return $response;
    }
    
    private function set_csrf_token_if_not_set() {
        # return token
        if ( ! isset($_SESSION['CSRFToken']) ) {
            $_SESSION['CSRFToken'] = sha1(getmicrotime());
        }
        return $_SESSION['CSRFToken'];
    }
    
    private function authenticate_csrf_token() {
        $posted_token = $this->request->get_post_var('CSRFToken');
        $session_token = $_SESSION['CSRFToken'];
        
        if ( $_POST ) {
            if ( ! $posted_token ) {
                throw new WikkaCsrfError('Authentication failed: NoCSRFToken');
            }
            elseif ( $posted_token != $session_token ) {
                throw new WikkaCsrfError('Authentication failed: CSRFToken mismatch');
            }
        }
        
        return true;
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
