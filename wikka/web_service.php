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
require_once('wikka/templater.php');
require_once('libs/Wikka.class.php');



class WikkaWebService {
    
    /*
     * Properties
     */
    public $config = array();
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
    
    public function authenticate_if_locked() {
        # A simple locking mechanism WikkaWiki has long used to restrict access,
        # especially for doing upgrades.
        # TODO: For upgrade, restrict access to logged in admins
        if ( ! $this->site_is_locked() ) {
            return null;
        }
        
        if ( ! $this->is_authenticated_to_unlock_site() ) {
            $auth_f = 'WWW-Authenticate: Basic realm="%s Install/Upgrade Interface"';
            $auth_header = sprintf($auth_f, $this->config["wakka_name"]);
		
            header($auth_header);
            header("HTTP/1.0 401 Unauthorized");
            throw new BasicAuthenticationError(T_(
                "This site is currently being upgraded. Please try again later."
            ));
        }
        
        return null;
    }
    
    public function enforce_csrf_token() {
        $token = $this->set_csrf_token_if_not_set();
        $this->authenticate_csrf_token();
        return $token;
    }
    
    public function process_request() {
        $route = $this->route_request();
        $handler_response = $this->run_wikka_handler($route['page'], $route['handler']);
        
        $wikka = WikkaBlob::autoload($this->config, $route['page'], $route['handler']);
        $templater = new WikkaTemplater($wikka);
        $templater->set('content', $handler_response->body);
        $handler_response->body = $templater->output();
        
        # Need wikka to retrieve header and footer from theme
        /*$wikka = WikkaBlob::autoload($this->config, $route['page'], $route['handler']);
        $body_parts = array(
            $wikka->Header(),
            $handler_response->body,
            $wikka->Footer()
        );
        $new_body = implode("\n", $body_parts);
        $handler_response->body = $new_body;
        */
        # Set common headers
        $handler_response->set_header('Cache-Control', 'no-cache');
        $handler_response->set_header('ETag', md5($handler_response->body));
        $handler_response->set_header('Content-Length', strlen($handler_response->body));
        
        return $handler_response;
    }
    
    public function process_error($error) {
        #
        # Process error and display within the regular page template. Hopefully
        # no other errors will occur at this point.
        #
        # TODO: make the templating more foolproof. Seems too easy currently to
        # have another error occur and end up displaying an ugly (and potentially
        # insecure) error message.
        #
        $route = $this->route_request();
        
        $wikka = WikkaBlob::autoload($this->config, $route['page'], $route['handler']);
        $content_items = array(
            $wikka->Header(),
            $wikka->format_error($error->getMessage()),
            $wikka->Footer()
        );
        
        if ( $error instanceof WikkaAccessError ) {
            $content = sprintf($error->template, $content_items[1]);
            $response = new WikkaResponse($content, 401);
        }
        else {
            $content = implode("\n", $content_items);
            $response = new WikkaResponse($content, 500);
        }
        
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
        $wikka = WikkaBlob::autoload($this->config, $page_name, $handler_name);
        $wikka->save_session_to_db();
        $handler_response = $wikka->Run($page_name, $handler_name);
        return $handler_response;
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
        # Load default settings
        require(WIKKA_DEFAULT_CONFIG_PATH);
        
        # If config file is missing, return default settings to trigger install
        if ( ! file_exists($config_file_path) ) {
            return $wakkaDefaultConfig;
        }
        
        # Load config settings
        include($config_file_path);
        
        # If $wakkaConfig not set, return default settings to trigger install
        if ( ! isset($wakkaConfig) ) {
            return $wakkaDefaultConfig;
        }
        
        # Overwrite defaults with config file settings
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
    
    private function site_is_locked() {
        return file_exists('locked');
    }
    
    private function is_authenticated_to_unlock_site() {
        # read password from lockfile
        $lines = file_get_contents("locked");
        $lockpw = trim($lines);
        
        return isset($_SERVER["PHP_AUTH_USER"]) && (
            ($_SERVER["PHP_AUTH_USER"] == "admin") &&
            ($_SERVER["PHP_AUTH_PW"] == $lockpw)
        );
    }
}
