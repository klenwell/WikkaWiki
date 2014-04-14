<?php
/**
 * Wikka Base Handler
 *
 * Base handler class for new refactored Wikka handlers.
 *
 * The main method to be called is the handle method. This should return
 * a WikkaResponse object. The response should have the body property set
 * to include the handler's content and have a status and content-type
 * header set for it.
 * 
 *
 * USAGE
 *  require_once('handlers/base.php');
 *
 *  class MyHandler extends WikkaHandler {
 *  }
 *
 *
 * @package		Handlers
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');
 
 
class WikkaHandler {
    
    /*
     * Properties
     */
    # Set this to true to display errors
    public $debug = true;
    
    # Handler should specify a type for Content-type header
    public $content_type = 'text/html';
    
    # Template
    public $template = <<<HTML
<div id="content">
    %s
    <div style="clear: both"></div>
</div>
HTML;

    # Template Vars (%s from template above in order)
    public $template_var = 'This template should be overridden';
    
    # Error output
    public $error = 'There was an unspecified error.';
    
    # Wikka object
    protected $request = null;
    protected $config = array();
    
    /*
     * Constructor
     */
    public function __construct($request) {
        $this->request = $request;
        $this->config = WikkaResources::$config;
        
        if ( $this->debug ) {
            error_reporting(E_ALL);
        }
    }
    
    /*
     * Main Handler Method
     */
    public function handle() {
        # This should return a WikkaResponse object
        trigger_error('this method should be overridden', E_USER_WARNING);
    }
    
    /*
     * Validation Methods
     */
    public function is_valid() {
        trigger_error('This should be overridden', E_USER_WARNING);
        
        if ( false ) {
            $this->error = T_("Error message goes here");
            return false;
        }
        
        return true;
    }
    
    /*
     * Format Methods
     */
    protected function show_error() {
        $this->page_content = sprintf('<div class="handler-error">%s</div>',
            $this->error);
        return $this->format_content();
    }
    
    protected function format_content() {
        trigger_error('This should be overridden', E_USER_WARNING);
        return sprintf($this->template,
            $this->template_var);
    }
}
