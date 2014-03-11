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
class WikkaResponse {
    /*
     * Properties
     */
    public $status = 0;
    
    private $config = null;
    private $headers = array();
    private $body = '';

    /*
     * Constructor
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /*
     * Public Methods
     */
    public function run_wikka_handler($page, $handler) {
        $wikka = new WikkaBlob($this->config);
        $wikka->open_buffer();
        $wikka->connect_to_db();
        $wikka->save_session_to_db();
        
        # TODO(klenwell): Ugh... the formatter class requires a $wakka var
        # which is a global instance of the Wakka class. So we provide it here.
        global $wakka;
        $wakka = $wikka;
        
        # Now we can call Run method and get out output
        $wikka->Run($page, $handler);
        $this->body = $wikka->close_buffer();
        return $wikka;
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
    
    public function render() {
        print($this->body);
    }
    
    /*
     * Private Methods
     */
}