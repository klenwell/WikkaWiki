<?php
/**
 * wikka/request.php
 *
 * WikkaRequest class. Object used by WikkaWebService object to encapsulate
 * request data.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 *
 */

 
 
class WikkaRequest {
    
    const DEFAULT_HANDLER = 'show';
    
    /*
     * Properties
     */
    public $route = array();
    public $domain = '';
    public $scheme = '';
    public $port = '';
    public $wikka_path = '';
    public $query_string = '';
    public $rewrite_on = false;
    public $params = array();
    
    public $wikka_base_domain_url = '';
    public $wikka_base_url_path = '';
    public $wikka_base_url = '';
    public $wikka_cookie_path = '';

    /*
     * Constructor
     */
    public function __construct() {
        # Set params
        $this->params = $this->extract_params();
        
        # Parse url
        $this->domain = $_SERVER['SERVER_NAME'];
        $this->port = $_SERVER['SERVER_PORT'];
        $this->query_string = $_SERVER['QUERY_STRING'];
        $this->scheme = $this->extract_scheme();
        $this->rewrite_on = $this->extract_rewrite_mode($this->wikka_path);
        $this->wikka_path = $this->extract_wikka_request_path($this->rewrite_on);
        $this->wikka_query_string = ( $this->rewrite_on ) ? '' : '?wakka=';
        
        # Set route
        $this->route = $this->set_route();
        
        # Build composite urls for constants
        $this->wikka_base_domain_url = $this->build_base_domain_url();
        $this->wikka_base_url_path = str_replace('wikka.php', '',
            $_SERVER['SCRIPT_NAME']);
        $this->wikka_base_url = $this->wikka_base_domain_url .
            $this->wikka_base_url_path;
        
        # Cookie path
        $this->wikka_cookie_path = $this->build_cookie_path();
    }
    
    /*
     * Public Methods
     */
    public function get_param($key, $default=null) {
        if ( isset($this->params[$key]) ) {
            return $this->params[$key];
        }
        else {
            return $default;
        }
    }
    
    public function get_get_var($key, $default=null) {
        if ( isset($_GET[$key]) ) {
            return $_GET[$key];
        }
        else {
            return $default;
        }
    }
    
    public function get_post_var($key, $default=null) {
        if ( isset($_POST[$key]) ) {
            return $_POST[$key];
        }
        else {
            return $default;
        }
    }
    
    public function define_constants() {
        define_constant_if_not_defined('WIKKA_BASE_DOMAIN_URL',
            $this->wikka_base_domain_url);
        define_constant_if_not_defined('WIKKA_BASE_URL_PATH',
            $this->wikka_base_url_path);
        define_constant_if_not_defined('WIKKA_BASE_URL',
            $this->wikka_base_url);
        define_constant_if_not_defined('WIKKA_COOKIE_PATH',
            $this->wikka_cookie_path);
    }
    
    /*
     * Private Methods
     */
    private function set_route() {
        $page = null;
        $handler = null;
        
        # Get wakka param (strip first slash)
        $wakka_param = $this->get_param('wakka', '');
        $wakka_param = preg_replace("/^\//", "", $wakka_param);
        
        # Extract pagename and handler from URL
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
        
        # If not hanlder set default
        if ( is_null($handler) ) {
            $handler = self::DEFAULT_HANDLER;
        }
        
        return array('page' => $page, 'handler' => $handler);
    }
    
    private function extract_params() {
        # Wakka::GetSafeVar used to sanitize GET vars. This is not
        # recommended as a general solution. For proper usage, see:
        # http://stackoverflow.com/a/1315779/1093087
        if ( $_GET ) {
            return $_GET;
        }
        else {
            return array();
        }
    }
    
    private function extract_scheme() {
        # See http://stackoverflow.com/a/5100206/1093087
        if ( ! empty($_SERVER['HTTPS']) ) {
            return 'https://';
        }
        else {
            return 'http://';
        }
    }
    
    private function extract_rewrite_mode() {
        $wakka_in_request_uri = preg_match('@wakka=@', $_SERVER['REQUEST_URI']);
        $has_query_string = isset($_SERVER['QUERY_STRING']);
        $wakka_in_query_string = preg_match('@wakka=@',$_SERVER['QUERY_STRING']);
        
        $in_rewrite_mode = (! $wakka_in_request_uri) && ($has_query_string &&
            $wakka_in_query_string);
        return $in_rewrite_mode;
    }
    
    private function extract_wikka_request_path($in_rewrite_mode) {
        $is_php_uri = preg_match('@\.php$@', $_SERVER['REQUEST_URI']);
        $is_wikka_php_uri = preg_match('@wikka\.php$@', $_SERVER['REQUEST_URI']);
        
        if ( $is_php_uri && (! $is_wikka_php_uri) ) {
            $request_path = preg_replace('@/[^.]+\.php@', '/wikka.php',
                $_SERVER['REQUEST_URI']);
        }
        else {
            $request_path = $_SERVER['REQUEST_URI'];
        }
        
        # remove 'wikka.php' and request (page name) from 'request' part: should
        # not be part of base_url!
        if ( $in_rewrite_mode ) {
            $request_path = str_replace('wikka.php', '', $request_path);
            $query_part = str_replace('wakka=', '', $_SERVER['QUERY_STRING']);
            
            $regex = sprintf('@%s@', preg_quote($query_part));
            $request_path = preg_replace($regex, '', $request_path);
        }
        
        return $request_path;
    }
    
    private function build_base_domain_url() {
        if (($this->scheme == 'http://') && ($this->port == '80')) {
            $port = '';
        }
        elseif (($this->scheme == 'https://') && ($this->port == '443')) {
            $port = '';
        }
        else {
            $port = sprintf(':%s', $this->port);
        }
        
        return sprintf('%s%s%s', $this->scheme, $this->domain, $port);
    }
    
    private function build_cookie_path() {
        if ( ($this->wikka_base_url == '/') ) {
            return '/';
        }
        else {
            return substr($this->wikka_base_url_path, 0, -1);
        }
    }
}
