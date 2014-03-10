<?php
/**
 * wikka/response.php
 *
 * WikkaReponse class. Object used by WikkaWebService object to deliver
 * response.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 *
 */
class WikkaReponse {
    /*
     * Properties
     */
    public $status = 0;
    
    private $headers = array();
    private $body = '';

    /*
     * Constructor
     */
    public function __construct() {
    }
    
    /*
     * Public Methods
     */
    public function run_wikka_handler($page, $handler) {
    }
    
    public function set_header($key, $value) {
        $this->headers[$key] = $value;
        return $this->headers;
    }
    
    public function send_headers() {
        foreach ($this->headers as $key => $value) {
            $header = sprintf('%s: %s', $key, $value);
            header($header);
        }
        
        if ( $this->status ) {
            $header = sprintf('X-PHP-Response-Code: %d', $this->status);
            header($header, true, $this->status);
        }
    }
    
    public function output_body() {
        print $this->body;
    }
    
    /*
     * Private Methods
     */
}