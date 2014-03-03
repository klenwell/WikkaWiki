<?php
/**
 * Wikka Base Handler
 *
 * Base handler class for new refactored Wikka handlers.
 *
 * USAGE
 *  require_once();
 *
 *  class MyHandler extends WikkaHandler {
 *  }
 *
 * NOTES
 * 
 *
 * @package		Handlers
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */

class WikkaHandler {
    
    /*
     * Properties
     */
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
    public $wikka = null;
    
    /*
     * Constructor
     */
    public function __construct($wikka_object) {
        $this->wikka = $wikka_object;
    }
    
    /*
     * Main Handler Method
     */
    public function handle() {
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
    public function show_error() {
        $this->page_content = sprintf('<div class="handler-error">%s</div>',
            $this->error);
        return $this->format_content();
    }
    
    public function format_content() {
        trigger_error('This should be overridden', E_USER_WARNING);
        return sprintf($this->template,
            $this->template_var);
    }
}
