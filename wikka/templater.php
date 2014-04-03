<?php
/**
 * wikka/templater.php
 *
 * WikkaTemplater class. Object used by WikkaWebService object to construct
 * output.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 *
 */
class WikkaTemplater {
    /*
     * Properties
     */
    public $layout = <<<HTML5
<!DOCTYPE html>
<html lang="en">
  {{head}}
  <body>
    {{header}}
    {{content}}
    {{footer}}
  </body>
</html>
HTML5;

    public $head = '';
    public $header = '';
    public $content = '';
    public $footer = '';
    
    private $config = array();

    /*
     * Constructor
     */
    public function __construct($wikka) {
        # TODO: eliminate the wikka dependencies. For now, we use it just
        # because it's more expedient.
        $this->wikka = $wikka;
        $this->config = $wikka->config;
    }
    
    /*
     * Public Methods
     */
    public function set($token, $value) {
        $current_value = $this->$token;
        $this->$token = $value;
        return $current_value;
    }
    
    public function output() {
        $tokens = array('{{head}}', '{{header}}', '{{content}}', '{{footer}}');
        $blocks = array($this->head, $this->header, $this->content, $this->footer);
        
        return str_replace(
            $tokens,
            $blocks,
            $this->layout
        );
    }
    
    /*
     * Helper Methods
     */
    
    /*
     * Private Methods
     */
}