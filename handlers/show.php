<?php
/**
 * Refactored Wikka Show Handler
 *
 * Display a page if the user has read access or is an admin.
 *
 * This is the default page handler used by Wikka when no other handler is specified.
 * It is used by the Wakka class to produce HTML output.
 *
 * Depending on user privileges, it returns the page body or an error message. It also
 * includes footer comments and a form to post comments, depending on ACL and general
 * config settings.
 *
 * USAGE
 *
 * NOTES
 * A refactor of the Wikka show handler to function as a more independent
 * modular unit that can be more effectively tested.
 *
 * @package     Handlers
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('handlers/base.php');
require_once('wikka/errors.php');
require_once('wikka/response.php');
require_once('wikka/registry.php');
require_once('models/user.php');
require_once('models/comment.php');
require_once('libs/Wikka.class.php');



class ShowHandler extends WikkaHandler {

    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html; charset=utf-8';

    # Template
    public $template = <<<HTML
<div id="content"%s>
    %s
    %s
    <div style="clear: both"></div>
</div>

%s
HTML;

    # Models
    private $page = null;
    private $user = null;

    # Template Vars (%s from template above in order)
    protected $double_click_edit = '';
    protected $revision_info = '';
    protected $page_content = '';
    protected $comment_block = '';

    # Comment display modes (str => int)
    private $comment_display_modes = array(
        'none' => COMMENT_NO_DISPLAY,
        'date_asc' => COMMENT_ORDER_DATE_ASC,
        'date_desc' => COMMENT_ORDER_DATE_DESC,
        'threaded' => COMMENT_ORDER_THREADED,
    );

    /*
     * Constructor
     */
    public function __construct($request) {
        parent::__construct($request);
        $this->page = $this->load_page($request);
        $this->user = UserModel::load();
    }

    /*
     * Main Handler Method
     */
    public function handle() {
        if ( ! $this->request_is_valid() ) {
            return $this->show_error();
        }

        if ( $this->double_click_is_active() ) {
            $this->double_click_edit = sprintf(' ondblclick="document.location=\'%s\'"',
                $this->href($this->page->fields['tag'], 'edit', array('id' => $this->page))
            );
        }

        if ( ! $this->page->is_latest_version() ) {
            $this->revision_info = $this->format_revision_info();
        }

        if ( $this->raw_page_requested() ) {
            $this->page_content = $this->format_raw_page_content();
        }
        else {
            $this->page_content = $this->format_page_content();
        }

        if ( $this->show_comments() ) {
            $comments = $this->load_comments();
            $this->comment_block = $this->format_comments($comments);
        }

        $content = $this->format_content();

        $response = new WikkaResponse($content);
        $response->status = 200;
        $response->set_header('Content-Type', $this->content_type);
        return $response;
    }

    /*
     * Validation Methods
     */
    private function request_is_valid() {
        if ( ! $this->page->tag_is_valid() ) {
            $this->error = sprintf(
                T_("This page name is invalid. " +
                   "Valid page names must not contain the characters %s."),
                WIKKA_INVALID_CHARS);
            return false;
        }

        if ( ! $this->page->exists() ) {
            $create_link = sprintf('<a href="%s">%s</a>',
                $this->href($this->page->fields['tag']),
                T_("create"));

            $this->error = sprintf("<p>%s</p>\n",
                sprintf(
                    T_("This page doesn't exist yet. Maybe you want to %s it?"),
                    $create_link
                )
            );
            return false;
        }

        if ( ! $this->user->can('read', $this->page) ) {
            $this->error = T_("You are not allowed to read this page.");
            return false;
        }

        return true;
    }

    /*
     * Status Methods
     */
    private function double_click_is_active() {
        return ($this->user->field('doubleclickedit') == 'Y') &&
            $this->user->can('write', $this->page);
    }

    private function raw_page_requested() {
        return $this->request->get_get_var('raw', FALSE);
    }

    /*
     * Comment Methods
     */
    private function show_comments() {
        return (WikkaRegistry::get_config('hide_comments') != 1) &&
            $this->user->can('comment_read', $this->page);
    }

    private function load_comments() {
        $page_tag = $this->page->fields['tag'];
        $order = $this->get_requested_comment_display_mode();
        return CommentModel::find_by_page_tag_as_array($page_tag, $order);
    }

    private function get_requested_comment_display_mode() {
        $display_mode = null;
        $display_modes = array_values($this->comment_display_modes);

        # Params
        $page_tag = $this->page->fields['tag'];
        $wants_comments = $this->user->wants_comments_for_page($this->page);

        # Init display mode
        if ( isset($_SESSION['show_comments'][$page_tag]) ) {
            $display_mode = $_SESSION['show_comments'][$page_tag];
        }

        if ( !(isset($_SESSION['show_comments'][$page_tag])) &&
            $wants_comments !== FALSE ) {
            $display_mode = $wants_comments;
        }

        # GET value holds precedence
        if ( isset($_GET['show_comments']) ) {
            $requested_mode = $this->request->get_get_var('show_comments');
            if ( in_array($requested_mode, $display_modes) ) {
                $display_mode = $requested_mode;
            }
        }

        $display_mode = (int) $display_mode;
        $_SESSION['show_comments'][$page_tag] = $display_mode;
        return $display_mode;
    }

    private function get_preferred_comment_display_mode() {
        $display_mode = null;

        # Determine preference
        if ( isset($this->user->fields['default_comment_display']) ) {
            $display_mode = $this->user->fields['default_comment_display'];
        }
        elseif ( !(is_null(WikkaRegistry::get_config('default_comment_display'))) ) {
            $display_mode = WikkaRegistry::get_config('default_comment_display');
        }

        if ( isset($this->comment_display_modes[$display_mode]) ) {
            return $this->comment_display_modes[$display_mode];
        }
        else {
            return COMMENT_ORDER_THREADED;
        }
    }

    private function collapse_comments() {
        $display_mode = $this->get_requested_comment_display_mode();
        return $display_mode == COMMENT_NO_DISPLAY;
    }

    private function show_delete_button_for_comment($comment) {
        /*
         * Conditions for which delete button is displayed:
         * 1. Current user owns the page the comment is on:
         * 2. Current user owns the comment;
         * 3. Current non-logged-in user matches IP or hostname of comment
         */
        $is_logged_in = $this->user->is_logged_in();
        $is_page_owner = $this->page->is_owned_by($this->user);
        $current_user = $this->user->field('name');
        $is_comment_owner = ($current_user == $comment['user']);

        if ( $is_logged_in && $is_page_owner ) {
            return true;
        }
        elseif ( $is_logged_in && $is_comment_owner ) {
            return true;
        }
        elseif ( $this->config['anony_delete_own_comments'] && $is_comment_owner ) {
            return true;
        }
        else {
            return false;
        }
    }

    /*
     * Helper Methods
     */
    private function format_user($user_name, $as_link=TRUE) {
        $format = '<span class="%s">%s</span>';

        if ( ! $user_name ) {
            return 'anonymous';
        }

        $user = UserModel::find_by_name($user_name);

        if ( $user->exists() ) {
            $class = 'user';
            $user_page = PageModel::find_by_tag($user_name);

            if ( $user_page->exists() && $as_link ) {
                $label = $user_name;
                $href = $this->href($user_name);
                $attrs = array(
                    'title' => sprintf('Open user profile for %s', $user_name)
                );
                $user_name = $this->build_link($href, $label, $attrs);
            }
        }
        else {
            $class = 'user_anonymous';
        }

        return sprintf($format, $class, $user_name);
    }

    private function render_using_formatter($text, $formatter='wakka', $options='') {
        if ( ! preg_match(RE_VALID_FORMATTER_NAME, $formatter) ) {
            throw new WikkaFormatterError(T_(
                'Formatter name contains invalid characters'));
        }

        $formatter = strtolower($formatter);
        $formatter_fname = sprintf('%s.php', $formatter);
        $formatter_path = sprintf('formatters%s%s', DIRECTORY_SEPARATOR,
            $formatter_fname);

        if ( ! file_exists($formatter_path) ) {
            throw new WikkaFormatterError(sprintf(T_(
                'Formatter "%s" not found'), $formatter_path));
        }

        /*
         * TODO: remove this when Formatter refactored
         */
        $wikka = WikkaBlob::autoload($this->config, $this->request->route['page']);

        ob_start();
        include($formatter_path);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public function GetHandler() {
        /*
         * Required by formatter in render_using_formatter
         * TODO: remove when Formatter refactored
         */
        return $this->request->route['handler'];
    }

    /*
     * Format Methods
     */
    protected function format_content() {
        return sprintf($this->template,
            $this->double_click_edit,
            $this->revision_info,
            $this->page_content,
            $this->comment_block);
    }

    private function format_revision_info() {
        # Format vars
        $format = <<<HTML
    <div class="revisioninfo">
        <h4 class="clear">%s</h4>
        <div class="message">%s</div>
        <div class="buttons">
            %s
            %s
        </div>
        <div class="clear"></div>
    </div>
HTML;
        $revision_header = '';
        $revision_message = '';
        $show_formatting_form = '';
        $edit_revision_form = '';

        # Params
        $page_data = $this->page->fields;
        $wants_raw_page = $this->raw_page_requested();

        # Set revision header
        $revision_link = sprintf('<a href="%s">[%s]</a>',
            $this->href($page_data['tag'], 'show', array('time' => $page_data['time'])),
            $page_data['id']
        );
        $revision_header = sprintf(T_('Revision %s'), $revision_link);

        # Set revision message
        $page_link = sprintf('<a href="%s">%s</a>',
            $this->href($page_data['tag']),
            $this->page->pretty_page_tag()
        );
        $revision_message = sprintf(
            T_("This is an old revision of %s made by %s on %s."),
            $page_link,
            $this->format_user($page_data['user']),
            $this->wiki_link($page_data['tag'], 'revisions', $page_data['time'])
        );

        # Show formatting form
        $formatting_form_f = <<<XHTML
            %s
                <input type="hidden" name="time" value="%s" />
                <input type="hidden" name="raw" value="%s" />
                <input type="submit" value="%s" />
            %s
XHTML;
        if ( $page_data ) {
            $show_formatting_form = sprintf($formatting_form_f,
                $this->open_form('', 'show', 'GET', array('class' => 'left')),
                $this->request->get_get_var('time', ''),
                ($wants_raw_page) ? '0' :'1',
                ($wants_raw_page) ? T_("Show formatted") : T_("Show source"),
                $this->close_form()
            );
        }

        # Edit revision form
        $revision_form_f = <<<XHTML
            %s
                <input type="hidden" name="previous" value="%s" />
                <input type="hidden" name="body" value="%s" />
                <input type="submit" name="submit" value="%s" />
            %s
XHTML;
        if ( $page_data && $this->user->can('write', $this->page) ) {
            $edit_revision_form = sprintf($revision_form_f,
                $this->open_form($page_data['tag'], 'edit'),
                $page_data['id'],
                htmlspecialchars($page_data['body']),
                T_("Re-edit this old revision"),
                $this->close_form()
            );
        }

        return sprintf($format,
            $revision_header,
            $revision_message,
            $show_formatting_form,
            $edit_revision_form
        );
    }

    private function format_raw_page_content() {
        $format = <<<XHTML
            <div class="wikisource">
                %s
            </div>
XHTML;
        return sprintf($format,
            nl2br(htmlspecialchars(
                $this->page->field('body'),
                ENT_QUOTES
            ))
        );
    }

    private function format_page_content() {
        return $this->render_using_formatter($this->page->fields['body'],
            'wakka', 'page');
    }

    /*
     * Comment Format Methods
     */
    private function format_comments($comments) {
        $format = <<<XHTML
            <!-- starting comments block-->
            <div id="comments">
                %s
                %s
            </div>
            <!--closing comments block-->
XHTML;
        $comment_header = $this->format_comment_header();

        if ( $this->collapse_comments() ) {
            $comment_list = '';
        }
        elseif ( $this->get_requested_comment_display_mode() == COMMENT_ORDER_THREADED ) {
            $comment_list = $this->format_threaded_comment_list($comments);
        }
        else {
            $comment_list = $this->format_comment_list($comments);
        }

        return sprintf($format, $comment_header, $comment_list);
    }

    private function format_threaded_comment_list($comments) {
        $format = <<<XHTML
                <div class="commentscontainer">
                    %s
                </div>
XHTML;
        $comment_list = '';

        $html = array();
        $previous = array('level' => -1);

        # Nests comments. Assume comments are ordered in COMMENT_ORDER_THREADED mode.
        foreach( $comments as $comment ) {
            $comment['level'] = isset($comment['level']) ? $comment['level'] : 0;
            $html[] = $this->close_parent_comments($comment, $previous);

            if ( $comment['status'] == 'deleted' ) {
                $html[] = $this->format_deleted_comment($comment, true);
            }
            else {
                $html[] = $this->format_threaded_comment($comment);
            }

            $previous = $comment;
        }

        # Close final comments
        $comment = array('level' => 0);
        $html[] = $this->close_parent_comments($comment, $previous);

        $comment_list = implode($html);
        return sprintf($format, $comment_list);
    }

    private function format_comment_list($comments) {
        $format = <<<XHTML
                <div class="commentscontainer">
                    %s
                </div>
XHTML;
        $comment_list = '';

        $formatted_comments = array();
        foreach( $comments as $comment ) {
            if ( $comment['status'] == 'deleted' ) {
                $formatted_comments[] = $this->format_deleted_comment($comment);
            }
            else {
                $formatted_comments[] = $this->format_comment($comment);
            }
        }
        $comment_list = implode('', $formatted_comments);

        return sprintf($format, $comment_list);
    }

    private function format_comment_header() {
        $format = <<<XHTML
                <div id="commentheader">
                    %s %s
                    %s
                </div>
XHTML;
        $header_title = '';
        $display_link = '';
        $comment_form = '';

        if ( $this->collapse_comments() ) {
            $comment_count = CommentModel::count_by_page_tag($this->page->field('tag'));
            $display_mode = $this->get_preferred_comment_display_mode();
            $header_title = $this->format_comment_count($comment_count);

            if ( $comment_count < 1 ) {
                if ( $this->user->can('comment_post', $this->page) ) {
                    $comment_form = $this->format_comment_form();
                }
            }
            else {
                $params = array('show_comments' => $display_mode);
                $label = ($comment_count == 1) ? T_("Show comment") : T_("Show comments");
                $display_link = sprintf('[<a href="%s#comments">%s</a>]',
                    $this->href($this->page->field('tag'), 'show', $params),
                    $label
                );
            }
        }
        else {
            $params = array('show_comments' => '0');
            $header_title = T_("Comments");
            $display_link = sprintf('[<a href="%s">%s</a>]',
                $this->href($this->page->field('tag'), 'show', $params),
                T_("Hide comments")
            );

            if ( $this->user->can('comment_post', $this->page) ) {
                $comment_form = $this->format_comment_form();
            }
        }

        return sprintf($format, $header_title, $display_link, $comment_form);
    }

    private function format_threaded_comment($comment) {
        # Notice it leaves the div unclosed
        $format = <<<XHTML
                    <div id="comment_%s" class="%s" >
                        <div class="commentheader">
                            <div class="commentauthor">%s</div>
                            <div class="commentinfo">%s</div>
                        </div>
                        <div class="commentbody">
                            %s
                        </div>
                        %s
XHTML;

        $comment_level = (isset($comment['level'])) ? $comment['level'] : 0;
        $comment_class = sprintf('comment-layout-%d', (($comment_level + 1) % 2) + 1);
        $comment_author = $this->format_user($comment['user']);
        $comment_byline = T_("Comment by ") . $comment_author;
        $comment_ts = sprintf("%s", $comment['time']);

        if ( $this->user->can('comment_post', $this->page) ) {
            $comment_action = $this->format_comment_action($comment);
        }
        else {
            $comment_action = '';
        }

        return sprintf($format,
            $comment['id'],
            $comment_class,
            $comment_byline,
            $comment_ts,
            $comment['comment'],
            $comment_action
        );
    }

    private function format_comment($comment) {
        $html = $this->format_threaded_comment($comment);
        return sprintf("%s%s</div>\n",
            $html,
            str_repeat(' ', 20)
        );
    }

    private function close_parent_comments($comment, $previous) {
        # If depth is greater, don't close yet
        if ( $comment['level'] > $previous['level'] ) {
            return '';
        }

        $diff = $previous['level'] - $comment['level'];

        $end_divs = array();
        foreach( range(0,$diff) as $n ) {
            $end_divs[] = '</div>';
        }

        return implode("\n", $end_divs);
    }

    private function format_comment_count($comment_count) {
        if ( $comment_count < 1 ) {
            return T_("There are no comments on this page.");
        }
        elseif ( $comment_count == 1 ) {
            return T_("There is one comment on this page.");
        }
        else {
            return sprintf(T_("There are %d comments on this page."), $comment_count);
        }
    }

    private function format_comment_form() {
        $format = <<<XHTML
                    %s
                    <input type="submit" name="submit" value="%s" />
                    %s
XHTML;
        $open_form_tag = $this->open_form($this->page->field('tag'), 'processcomment',
            'post', array('anchor' => 'comments'));
        $submit_value = T_("New Comment");
        $close_form_tag = $this->close_form();

        return sprintf($format, $open_form_tag, $submit_value, $close_form_tag);
    }

    private function format_deleted_comment($comment, $is_threaded=false) {
        $format = <<<XHTML
                    <div class="%s">
                        <div class="commentdeleted">
                            %s
                        </div>
                    %s
XHTML;
        $comment_class = '';
        $comment_body = T_("[Comment deleted]");
        $end_div = ($is_threaded) ? '' : '</div>';

        $comment_level = (isset($comment['level'])) ? $comment['level'] : 0;
        $comment_class = sprintf('comment-layout-%d', (($comment_level + 1) % 2) + 1);

        return sprintf($format, $comment_class, $comment_body, $end_div);
    }

    private function format_comment_action($comment) {
        $format = <<<XHTML
                        <div class="commentaction">
                            %s
                                <input type="hidden" name="comment_id" value="%s" />
                                <input type="submit" name="submit" value="%s" />
                                %s
                            %s
                        </div>
XHTML;

        $open_form_tag = $this->open_form($this->page->field('tag'), 'processcomment',
            'post', array('anchor' => 'comments'));
        $comment_id = $comment['id'];
        $submit_button_label = T_("Reply");
        $delete_button = '';
        $close_form_tag = $this->close_form();

        if ( $this->show_delete_button_for_comment($comment) ) {
            $delete_button = sprintf(
                '<input type="submit" name="submit" value="%s" />',
                T_("Delete")
            );
        }

        return sprintf($format,
            $open_form_tag,
            $comment_id,
            $submit_button_label,
            $delete_button,
            $close_form_tag
        );
    }
}
