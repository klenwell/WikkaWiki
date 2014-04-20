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
require_once('wikka/registry.php');
require_once('models/page.php');



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
        $this->config = WikkaRegistry::$config;

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

    /*
     * Protected Methods
     */
    protected function load_page($request) {
        $page = null;
        $revision_requested = $request->get_get_var('time', FALSE);

        if ( $revision_requested ) {
            $page = PageModel::find_by_tag_and_time(
                $request->route['page'],
                $revision_requested
            );
        }

        if ( $page ) {
            return $page;
        }
        else {
            return PageModel::find_by_tag($request->route['page']);
        }
    }

    protected function open_form($page_tag, $handler='', $method='post', $options=null) {
        /*
         * Does not support file uploads.
         */
        $attr_dict = array();
        $hidden_fields = array();

        # Optional args
        $id = ( isset($options['id']) ) ? $options['id'] : '';
        $class = ( isset($options['class']) ) ? $options['class'] : '';
        $anchor = ( isset($options['anchor']) ) ? $options['anchor'] : '';

        $page = PageModel::find_by_tag($page_tag);
        if ( ! $page->exists() ) {
            $page = $this->page;
        }
        $page_tag = $page->field('tag');

        # Set action attr
        $attr_dict['action'] = $this->href($page_tag, $handler);
        $attr_dict['method'] = strtolower($method);
        $attr_dict['id'] = $id;

        # Add anchor
        if ( $anchor ) {
            $attr_dict['action'] = sprintf('%s#%s', $attr_dict['action'], $anchor);
        }

        # If rewrite mode off, must add hidden field with page tag
        if ( ! WikkaRegistry::get_config('rewrite_mode') ) {
            $fs = ( $handler ) ? '/' : '';
            $hidden_fields['wakka'] = $page_tag . $fs . $handler;
        }

        # If id blank, generate an ID
        if ( ! $attr_dict['id'] ) {
            $md5 = md5($handler.$page_tag.$method.$class);
            $id = substr($md5, 0, ID_LENGTH);
            $attr_dict['id'] = generate_wikka_form_id('form', $id);
        }

        if ( $class ) {
            $attr_dict['class'] = $class;
        }

        # If POST form, add hidden field for CSRF token
        if ( $attr_dict['method'] == 'post' ) {
            $hidden_fields['CSRFToken'] = $_SESSION['CSRFToken'];
        }

        # Build attrs
        $attr_list = array();
        foreach ( $attr_dict as $attr => $value ) {
            $attr_list[] = sprintf('%s="%s"', $attr,
                str_replace('"', '\"', $value));
        }
        $attrs = sprintf(' %s', implode(' ', $attr_list));

        # Build hidden fieldset
        if ( $hidden_fields ) {
            $format = '<input type="hidden" name="%s" value="%s" />';
            $hidden_field_elements = array('<fieldset class="hidden">');
            foreach ( $hidden_fields as $name => $value ) {
                $element = sprintf($format, $name, $value);
                $hidden_field_elements[] = $element;
            }
            $hidden_field_elements[] = '</fieldset>';
            $hidden_fieldset = sprintf("\n%s", implode("\n", $hidden_field_elements));
        }
        else {
            $hidden_fieldset = '';
        }

        return sprintf("<form%s>%s", $attrs, $hidden_fieldset);
    }

    protected function close_form() {
        return "</form>\n";
    }

    /*
     * Helper Methods
     */
    protected function href($page_name, $handler='', $params=array()) {
        $in_rewrite_mode = (bool) $this->config['rewrite_mode'];
        $page_name = preg_replace('/\s+/', '_', $page_name);

        if ( $in_rewrite_mode ) {
            $wikka_url = $this->request->wikka_base_url;
        }
        else {
            $wikka_url = $this->request->wikka_base_url . WIKKA_URL_EXTENSION;
        }

        if ( $handler ) {
            $route = sprintf('%s/%s', $page_name, $handler);
        }
        else {
            $route = $page_name;
        }

        if ( $params ) {
            $hitch = ( $in_rewrite_mode ) ? '?' : '&';
            $query_string = $hitch . http_build_query($params);
        }
        else {
            $query_string = '';
        }

        return sprintf('%s%s%s', $wikka_url, $route, $query_string);
    }

    protected function build_link($href, $label=NULL, $attr_dict=array()) {
        $format = '<a href="%s"%s>%s</a>';

        if ( is_null($label) ) {
            $label = $href;
        }

        if ( $attr_dict ) {
            $attr_list = array();
            foreach ( $attr_dict as $attr => $value ) {
                $attr_list[] = sprintf('%s="%s"', $attr,
                    str_replace('"', '\"', $value));
            }
            $attrs = sprintf(' %s', implode(' ', $attr_list));
        }
        else {
            $attrs = '';
        }

        return sprintf($format, $href, $attrs, $label);
    }

    protected function wiki_link($page_tag, $handler=NULL, $label=NULL, $track=TRUE,
        $assume_page_exists=TRUE) {

        $href = '';
        $label = ( ! $label ) ? $page_tag : $label;
        $attr_dict = array();

        $page_tag = htmlspecialchars($page_tag);
        $handler = htmlspecialchars($handler);
        $label = htmlspecialchars($label);

        $is_fully_qualified_url = preg_match(RE_FULLY_QUALIFIED_URL, $page_tag);
        $is_email_address = preg_match(RE_EMAIL_ADDRESS, $page_tag);

        if ( $is_fully_qualified_url ) {
            $href = $page_tag;

            $re_pattern = sprintf('/%s/', preg_quote($_SERVER['SERVER_NAME']));
            if (! preg_match($re_pattern, $page_tag)) {
                $attr_dict['class'] = 'ext';
            }
        }
        elseif ( $is_email_address ) {
            $href = sprintf('mailto:%s', $page_tag);
            $attr_dict['class'] = 'mailto';
        }
        else {
            $page = PageModel::find_by_tag($page_tag);

            if (isset($_SESSION['linktracking']) && $_SESSION['linktracking']
                && $track) {
                $_SESSION['linktable'][] = $page_tag;
            }

            if ( (! $assume_page_exists) && (! $page->exists()) ) {
                $href = $this->href($page_tag, 'edit');
                $attr_dict['class'] = 'missingpage';
                $attr_dict['title'] = T_("Create this page");
            }
            else {
                $href = $this->href($page_tag, $handler);
            }
        }

        return $this->build_link($href, $label, $attr_dict);
    }
}
