<?php
/**
 * wikka/templater.php
 *
 * WikkaTemplater class. Object used by WikkaWebService object to construct
 * output.
 *
 * Layout includes partials (html/php files loaded in buffers) signified by
 * {{ foo }} or {{foo}}.
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
  {{ head }}
  <body>
    <div class="container">
      <div id="page-header">
        {{ header }}
      </div>
      
      <div id="handler-content">
        {{ content }}
      </div>
      
      <div id="page-controls" class="navbar">
        <div class="navbar-inner-disabled">
          <div class="container">
            {{ page_controls_menu }}
          </div>
        </div>
      </div>
      
    </div>
    
    <div id="footer">
      <div class="container">
        {{ footer }}
      </div>
    </div>
    
    {{underfoot}}
    
  </body>
</html>
HTML5;
    
    public $wikka = array();
    public $config = array();
    
    public $page_title = '';
    private $flash_message = '';
    
    protected $partial = array();
    
    private $theme_path = '';
    private $partials_path = '';
    private $menus_path = '';

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
        $this->partials_path = sprintf('%s%spartials', $this->theme_path,
            DIRECTORY_SEPARATOR);
        $this->menus_path = sprintf('%s%smenus.php', $this->theme_path,
            DIRECTORY_SEPARATOR);
        
        # Load layout
        $this->layout = $this->load_layout();
        
        # Set template values
        $this->page_title = sprintf('%s : %s',
            $this->escape_config('wakka_name', 'WikkaWiki'),
            $this->get_page_title()
        );
        $this->flash_message = $wikka->GetRedirectMessage();
    }
    
    /*
     * Public Methods
     */
    public function output() {
        $output = $this->layout;
        $partial_re = '/\{\{\s*[^\}]+\}\}/';
        $partials = array();
        
        $matched = preg_match_all($partial_re, $this->layout, $partials);
        
        foreach ( $partials[0] as $partial ) {
            $id = preg_replace('/[\{\}\s]/', '', $partial);
            $output = str_replace($partial, $this->load_partial($id), $output);
        }
        
        return $output;
    }
    
    public function set($name, $value) {
        $current_value = $this->load_partial($name);
        $this->partial[$name] = $value;
        return $current_value;
    }
    
    public function show_flash_message_if_set() {
        $format = <<<HTML5
      <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert"
          aria-hidden="true">&times;</button>
        %s
      </div>
HTML5;

        if ( $this->flash_message ) {
            return sprintf($format, $this->flash_message);
        }
        else {
            return '';
        }
    }
    
    /*
     * Partial Methods
     */
    protected function head() {
        $path = sprintf('%s%s%s',
            $this->partials_path, DIRECTORY_SEPARATOR, 'head.html.php');
        return $this->buffer($path);
    }
    
    protected function header() {
        $path = sprintf('%s%s%s',
            $this->partials_path, DIRECTORY_SEPARATOR, 'header.html.php');
        return $this->buffer($path);
    }
    
    protected function page_controls_menu() {
        return $this->menu('options_menu', 'nav navbar-nav');
    }
    
    
    protected function footer() {
        $path = sprintf('%s%s%s',
            $this->partials_path, DIRECTORY_SEPARATOR, 'footer.html.php');
        return $this->buffer($path);
    }
    
    protected function underfoot() {
        $path = sprintf('%s%s%s',
            $this->partials_path, DIRECTORY_SEPARATOR, 'underfoot.html.php');
        return $this->buffer($path);
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
    
    public function link($href, $text, $title='', $class='') {
        $handler = '';
        $track = true;
        $escapeText = true;
        $assumePageExists = true;

        return $this->wikka->Link($href, $handler, $text, $track,
            $escapeText, $title, $class, $assumePageExists);
    }
    
    public function open_form($tag, $id='', $class='', $method='post') {
        return $this->wikka->FormOpen('', $tag, $method, $id, $class);
    }
    
    public function close_form() {
        return "</form>\n";
    }
    
    public function get_page_title() {
        return $this->wikka->PageTitle();
    }
    
    public function get_page_tag() {
        return $this->wikka->GetPageTag();
    }
    
    public function get_user() {
        return $this->wikka->GetUser();
    }
    
    public function get_wikka_version() {
        $version = $this->wikka->GetWakkaVersion();
        $patch_level = '';
        
        if ( $this->wikka->GetWikkaPatchLevel() != '0' ) {
            $patch_level = sprintf('-p%s', $this->wikka->GetWikkaPatchLevel());
        }
            
        return sprintf('%s%s', $version, $patch_level);
    }
    
    public function is_admin() {
        return $this->wikka->IsAdmin();
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
    
    public function menu($menu, $ul_class='nav', $ul_id=null) {
        include($this->menus_path);
        $menu_array = $WikkaMenus[$menu];
        
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

        return $this->build_ul($menu_li, $ul_class, $ul_id);
    }
    
    public function build_search_form() {
        $html_f = "%s\n%s\n%s";
        
        $handler = '';
        $form_tag = 'TextSearch';
        $form_method ='get';
        $form_id = '';
        $form_class = 'navbar-search navbar-form';

        $input_tag_f = '<input type="text" id="%s" class="%s" name="%s" ' .
            'placeholder="%s" />';
        $input_id = 'searchbox';
        $input_name = 'phrase';
        $input_class = 'form-control searchbox search-query';
        $input_placeholder = 'Search';

        return sprintf($html_f,
            $this->open_form($form_tag, $form_id, $form_class, $form_method),
            sprintf($input_tag_f, $input_id, $input_class, $input_name,
                $input_placeholder),
            $this->close_form());
    }
    
    public function build_alternate_link($type, $title, $href) {
        $format = '<link rel="alternate" type="%s" title="%s" href="%s" />';
        return sprintf($format, $type, $title, $href);
    }
    
    /*
     * Debug Methods
     */
    public function output_sql_debugging() {
        $html_f = <<<HTML5
    <div id="sql_debug" class="smallprint">
        <h4>Query Log</h4>
        <table>
          <thead>
            <tr><th>query</th><th>time</th></tr>
          </thead>
          <tbody>
            %s
            <tr class="total">
                <td class="query">total time</td>
                <td class="time">%0.4f</td>
            </tr>
          </tbody>          
        </table>
    </div>        
HTML5;
        
        $query_tr = array();
        $tr_f = '<tr><td class="query">%s</td><td class="time">%0.4f</td></tr>';
        foreach ($this->wikka->queryLog as $query) {
            $query_tr[] = sprintf($tr_f, $query['query'], $query['time']);
        }

        printf($html_f, implode("\n", $query_tr), $this->get_load_time);
    }
    
    public function get_load_time() {
        return $this->wikka->microTimeDiff(WIKKA_TIMER_START);
    }
    
    public function output_load_time() {
        $f = T_("Page was generated in %.4f seconds");
        return sprintf($f, $this->get_load_time());
    }
    
    /*
     * Protected Methods
     */
    protected function buffer($path) {
        ob_start();
        include($path);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
    
    protected function build_drop_down($submenu, $href="#") {
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
    
    protected function build_ul($li_list, $class=null, $id=null) {
        # TODO: add this to an HtmlHelper class
        $ul_f = "<ul%s%s>\n%s\n</ul>";
        $lis = array();
        
        $ul_id = is_null($id) ? '' : sprintf(' id="%s"', $id);
        $ul_class = is_null($class) ? '' : sprintf(' class="%s"', $class);
        
        return sprintf($ul_f, $ul_id, $ul_class, implode("\n", $li_list));
    }
    
    protected function menu_li($wikka_item) {
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
     * Private Methods
     */
    private function load_layout() {
        # Looks for layout.php in with $WikkaLayout var in it. If not found,
        # uses default layout property of this class.
        $layout_file = sprintf('%s%slayout.php', $this->theme_path, DIRECTORY_SEPARATOR);
        
        if ( file_exists($layout_file) ) {
            include($layout_file);
        }
        else {
            return $this->layout;
        }
        
        if ( isset($WikkaLayout) ) {
            return $WikkaLayout;
        }
        else {
            return $this->layout;
        }
    }
    
    private function load_partial($id) {
        if ( method_exists($this, $id) ) {
            return $this->$id();
        }
        elseif ( isset($this->partial[$id]) ) {
            return $this->partial[$id];
        }
        else {
            return sprintf('<!-- block %s not found -->', $id);
        }
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