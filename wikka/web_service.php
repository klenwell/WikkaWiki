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
class WikkaWebServiceError extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }

    // custom string representation of object
    public function __toString() {
        return sprintf("%s: %s\n", __CLASS__, $this->message);
    }
}



class WikkaWebService {
    
    /*
     * Properties
     */
    public $config = array();
    public $pdo = null;
    
    private $route = array('page' => null, 'handler' => null);

    /*
     * Constructor
     */
    public function __construct($config_file_path=null) {
        if ( ! $config_file_path ) {
           $config_file_path = WIKKA_CONFIG_PATH;
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
    }
    
    private function load_config($config_file_path) {
        include(WIKKA_CONFIG_PATH);
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
