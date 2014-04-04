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
    private $menus_path = '';
    
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
        $this->menus_path = sprintf('%s/menus.php', $this->theme_path);
        
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
    
    public function get_page_tag() {
        return $this->wikka->GetPageTag();
    }
    
    public function build_masthead() {
        $html_f = '%s : %s';
        $homepage_f = '<a id="homepage_link" href="%s">%s</a>';
        $backlinks_f = '<a href="%s" title="%s">%s</a>';
        
        $homepage_link = sprintf($homepage_f,
            $this->wikka->href('', $this->escape_config('root_page'), ''),
            $this->escape_config('wakka_name', 'WikkaWiki'));
        
        $title = sprintf('Display a list of pages linking to %s',
            $this->get_page_tag());
        $backlinks_link = sprintf($backlinks_f,
            $this->wikka->href('backlinks', '', ''),
            $title,
            $this->get_page_tag());
          
        return sprintf($html_f, $homepage_link, $backlinks_link);
    }
    
    public function menu($menu) {
        include($this->menus_path);
        $menu_array = $BootstrapMenus[$menu];
        
        if ( $this->wikka->IsAdmin() ) {
            $menu_items = $menu_array['admin'];
        }
        elseif ( $this->wikka->GetUser() ) {
            $menu_items = $menu_array['user'];
        }
        else {
            $menu_items = $menu_array['default'];
        }
        
        $menu_li = array();
        foreach( $menu_items as $item ) {
            if (is_array($item)) {
                $menu_li[] = $this->build_drop_down($item);
            }
            else {
                $menu_li[] = $this->menu_li($item);
            }
        }

        return $this->build_ul($menu_li, 'nav');
    }
    
    public function build_search_form() {
        $html_f = "%s\n%s\n%s";
        
        $handler = '';
        $form_tag = 'TextSearch';
        $form_method ='get';
        $form_id = '';
        $form_class = 'navbar-search pull-right';
        
        $input_tag_f = '<input type="text" id="%s" class="%s" name="%s" ' .
            'placeholder="%s" />';
        $input_id = 'searchbox';
        $input_name = 'phrase';
        $input_class = 'searchbox search-query';
        $input_placeholder = 'Search';

        return sprintf($html_f,
            $this->wikka->FormOpen($handler, $form_tag, $form_method, $form_id,
                $form_class),
            sprintf($input_tag_f, $input_id, $input_class, $input_name,
                $input_placeholder),
            $this->wikka->FormClose());
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
        $path = sprintf('%s%s%s',
            $this->theme_path, DIRECTORY_SEPARATOR, 'header.html.php');
        return $this->buffer($path);
    }
    
    private function footer() {
        return 'FOOTER HERE';
    }
    
    private function build_drop_down($submenu, $href="#") {
        # TODO: add this to an HtmlHelper class
        $keys = array_keys($submenu);
        $head = $keys[0];
        $lis = $submenu[$head];
        $active_class = '';        
        
        $toggle_f = "<a href=\"%s\" %s>\n%s\n<b class=\"caret\"></b></a>";
        $a_toggle = sprintf($toggle_f,
            $href,
            'class="dropdown-toggle" data-toggle="dropdown"',
            $head);
        
        $li_list = array();
        foreach ( $lis as $li ) {
            $li_list[] = $this->menu_li($li);
        }
        
        $sub_ul = $this->build_ul($li_list, 'dropdown-menu');

        $li_f = "<li class=\"dropdown%s\">\n%s\n%s\n</li>";
        return sprintf($li_f, $active_class, $a_toggle, $sub_ul);
    }
    
    private function build_ul($li_list, $class=null, $id=null) {
        # TODO: add this to an HtmlHelper class
        $ul_f = "<ul%s%s>\n%s\n</ul>";
        $lis = array();
        
        $ul_id = is_null($id) ? '' : sprintf(' id="%s"', $id);
        $ul_class = is_null($class) ? '' : sprintf(' class="%s"', $class);
        
        return sprintf($ul_f, $ul_id, $ul_class, implode("\n", $li_list));
    }
    
    private function menu_li($wikka_item) {
        # pseudo-action formatters
        $contains_pseudo_action = preg_match('/<<([^>]+)>>/', $wikka_item,
            $match);
        if ( ! empty($contains_pseudo_action) ) {
            $tag = $match[1];
            $method = sprintf('%s_pseudoaction', $tag);
            $wikka_item = $this->$method($wikka_item, sprintf('<<%s>>', $tag));
        }
        
        $active = ($this->wikka->GetPageTag()) && 
            (strpos($wikka_item, $this->wikka->GetPageTag()) !== false);
        
        $class = '';
        if ( $active ) {
            $class = ' class="active"';
        }
        
        $li_f = "<li%s>%s</li>";
        return sprintf($li_f, $class, $this->wikka->Format($wikka_item));
    }
    
    /*
     * Pseudo-Actions (used by menus -- see menu_li)
     */
    private function username_pseudoaction($wikka_item, $tag) {
        return str_replace($tag, $this->wikka->GetUserName(), $wikka_item);
    }
    
    private function logout_pseudoaction() {
        # must escape wiki formatting
        return '""<a class="logout-click" href="#">Logout</a>""';
    }
}