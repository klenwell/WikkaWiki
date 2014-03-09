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
    public function prepare_request() {
        $request = new WikkaRequest();
        return $request;
    }
    
    public function process_request($request) {
        $route = $this->route_request($request);
        $response = new WikkaResponse();
        return $response;
    }
    
    public function process_error($error) {
        $reponse = new WikkaResponse();
        $response->status_code = 500;
        return $response;
    }
    
    /*
     * Private Methods
     */
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
    
    private function route_request() {
    }
}
