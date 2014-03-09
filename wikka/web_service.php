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

class WikkaWebService {
    
    /*
     * Properties
     */
    public $config = array();
    public $pdo = null;
    public $request = null;
    
    private $route = null;

    /*
     * Constructor
     */
    public function __construct() {
        $this->verify_requirements();
        $this->config = $this->load_config();
        $this->pdo = $this->connect_to_db();
    }
    
    /*
     * Public Methods
     */
    public function process_request() {
        $this->request = new WikkaRequest();
        $this->route = $this->route_request();
    }
    
    /*
     * Private Methods
     */
    private function verify_requirements() {
    }
    
    private function load_config() {
    }
    
    private function connect_to_db() {
    }
}
