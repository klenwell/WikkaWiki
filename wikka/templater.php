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
    <div class="container">
      <div class="page-header">
        {{header}}
      </div>
      <div class="content">
        {{content}}
      </div>
    </div>
    <div id="footer">
      <div class="container">
        {{footer}}
      </div>
    </div>
    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  </body>
</html>
HTML5;

    public $head = '';
    public $header = '';
    public $content = '';
    public $footer = '';
    
    private $wikka = array();
    private $config = array();
    
    private $theme_path = '';
    private $theme_css_path = '';
    private $theme_js_path = '';
    
    private $page_title = '';

    /*
     * Constructor
     */
    public function __construct($wikka) {
        # TODO: eliminate the wikka dependencies. For now, we use it just
        # because it's more expedient.
        $this->wikka = $wikka;
        $this->config = $wikka->config;
        
        # Set template paths
        $this->theme_path = $wikka->GetThemePath('/');
        $this->theme_css_path = sprintf('%s/css', $this->theme_path);
        $this->theme_js_path = sprintf('%s/js', $this->theme_path);
        
        # Set template values
        $this->page_title = sprintf('%s : %s',
            $this->escape_config('wakka_name', 'WikkaWiki'),
            $this->get_page_title());
        
        # Set default page blocks
        $this->set('head', $this->head());
        $this->set('header', $this->header());
        $this->set('footer', $this->footer());
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
    public function escape($value) {
        return $this->wikka->htmlspecialchars_ent($value);
    }
    
    public function get_config_value($key, $default='') {
        return ( isset($this->config[$key]) ) ? $this->config[$key] : $default;
    }
    
    public function escape_config($key) {
        return $this->escape($this->get_config_value($key));
    }
    
    public function get_page_title() {
        return $this->wikka->PageTitle();
    }
    
    /*
     * Private Methods
     */
    private function buffer($path) {
        ob_start();
        include($path);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
    
    private function head() {
        $path = sprintf('%s%s%s',
            $this->theme_path, DIRECTORY_SEPARATOR, 'head.html.php');
        return $this->buffer($path);
    }
    
    private function header() {
        return '<h1>HEADER HERE</h1>';
    }
    
    private function footer() {
        return 'FOOTER HERE';
    }
}