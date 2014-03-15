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

class ShowHandler extends WikkaHandler {
    
    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html';
    
    # Template
    public $template = <<<HTML
<div id="content"%s>
    %s
    %s
    <div style="clear: both"></div>
</div>

%s
HTML;

    # Template Vars (%s from template above in order)
    public $double_click_edit = '';
    public $revision_info = '';
    public $page_content = '';
    public $comment_block = '';
    
    # Comment display modes (str => int)
    public $comment_display_modes = array(
        'none' => COMMENT_NO_DISPLAY,
        'date_asc' => COMMENT_ORDER_DATE_ASC,
        'date_desc' => COMMENT_ORDER_DATE_DESC,
        'threaded' => COMMENT_ORDER_THREADED,
    );
    
    /*
     * Main Handler Method
     */
    public function handle() {
        if ( ! $this->request_is_valid() ) {
            return $this->show_error();
        }
        
        if ( $this->double_click_is_active() ) {
            $this->double_click_edit = sprintf(' ondblclick="document.location=\'%s\'"',
                $this->wikka->Href('edit', '', 'id='.$this->wikka->page['id'])
            );
        }
        
        if ( ! $this->page_is_latest() ) {
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
        $response->set_header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }
    
    /*
     * Validation Methods
     */
    public function request_is_valid() {
        if ( ! $this->user_has_read_access() ) {
            $this->error = T_("You are not allowed to read this page.");
            return false;
        }
        
        if ( ! $this->page_name_is_valid() ) {
            $this->error = sprintf(
                T_("This page name is invalid. " +
                   "Valid page names must not contain the characters %s."),
                SHOW_INVALID_CHARS);
            return false;
        }
        
        if ( ! $this->page_exists() ) {
            $create_link = sprintf('<a href="%s">%s</a>',
                $this->wikka->Href('edit'),
                T_("create"));
            
            $this->error = sprintf("<p>%s</p>\n",
                sprintf(
                    T_("This page doesn't exist yet. Maybe you want to %s it?"),
                    $create_link
                )
            );
            return false;
        }
        
        return true;
    }
    
    public function user_has_read_access() {
        return $this->wikka->HasAccess('read');
    }
    
    public function page_name_is_valid() {
        return $this->wikka->IsWikiName($this->wikka->GetPageTag());
    }
    
    public function page_exists() {
        return isset($this->wikka->page) && (! empty($this->wikka->page));
    }
    
    public function page_is_latest() {
        return isset($this->wikka->page['latest']) &&
            ($this->wikka->page['latest'] == 'Y');
    }
    
    /*
     * Status Methods
     */
    public function double_click_is_active() {
        $user = $this->user;
        return $user && ($user['doubleclickedit'] == 'Y') &&
            ($this->wikka->HasAccess('write'));
    }
     
    public function raw_page_requested() {
        # TODO(klenwell): make this less insane.
        # (bool) works as expected with '0' and '1'
        return (! empty($_GET['raw'])) &&
            ((bool) $this->wikka->GetSafeVar('raw', 'get'));
    }
    
    /*
     * Comment Methods
     */
    public function show_comments() {
        return ($this->wikka->GetConfigValue('hide_comments') != 1) &&
            $this->wikka->HasAccess('comment_read');
    }
    
    private function load_comments() {
        $page_tag = $this->page_tag;
        $order = $this->get_requested_comment_display_mode();
        return $this->wikka->LoadComments($page_tag, $order);
    }
    
    private function get_requested_comment_display_mode() {
        $display_mode = null;
        $display_modes = array_values($this->comment_display_modes);
        
        # Params
        $page_tag = $this->page_tag;
        $wants_comments = $this->wikka->UserWantsComments($page_tag);
        
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
            $requested_mode = $this->wikka->GetSafeVar('show_comments', 'get');
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
        
        # Params
        $user = $this->user;
        $configured_display = $this->wikka->GetConfigValue('default_comment_display');
        
        # Determine preference
        if ( isset($user['default_comment_display']) ) {
            $display_mode = $user['default_comment_display'];
        }
        elseif ( !(is_null($configured_display)) ) {
            $display_mode = $configured_display;
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
        $is_logged_in = $this->wikka->GetUser();
        $is_page_owner = $this->wikka->UserIsOwner();
        $current_user = $this->wikka->GetUserName();
        $is_comment_owner = ($current_user == $comment['user']);
        
        if ( $is_logged_in && $is_page_owner ) {
            return true;
        }
        elseif ( $is_logged_in && $is_comment_owner ) {
            return true;
        }
        elseif ( $this->wikka->config['anony_delete_own_comments'] &&
            $is_comment_owner ) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /*
     * Format Methods
     */
    public function format_content() {
        return sprintf($this->template,
            $this->double_click_edit,
            $this->revision_info,
            $this->page_content,
            $this->comment_block);
    }
    
    public function format_revision_info() {
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
        $page_data = $this->wikka->LoadPage($this->wikka->GetPageTag());
        $wants_raw_page = $this->raw_page_requested();
        
        # Set revision header
        $link_param = sprintf('time=%s', urlencode($this->wikka->page['time']));
        $revision_link = sprintf('<a href="%s">[%s]</a>',
            $this->wikka->Href('', '', $link_param),
            $this->wikka->page['id']
        );
        $revision_header = sprintf(T_('Revision %s'), $revision_link);
        
        # Set revision message
        $page_link = sprintf('<a href="%s">%s</a>',
            $this->wikka->Href(),
            $this->wikka->GetPageTag()
        );
        $revision_message = sprintf(
            T_("This is an old revision of %s made by %s on %s."),
            $page_link,
            $this->wikka->FormatUser($this->wikka->page['user']),
            $this->wikka->Link($this->wikka->GetPageTag(), 'revisions',
                $this->wikka->page['time'], TRUE, TRUE, '', 'datetime')
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
                $this->wikka->FormOpen('show', '', 'GET', '', 'left'),
                $this->wikka->GetSafeVar('time', 'get'),
                ($wants_raw_page) ? '0' :'1',
                ($wants_raw_page) ? T_("Show formatted") : T_("Show source"),
                $this->wikka->FormClose()
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
        if ( $page_data && $this->wikka->HasAccess('write') ) {
            $edit_revision_form = sprintf($revision_form_f,
                $this->wikka->FormOpen('edit'),
                $page_data['id'],
                $this->wikka->htmlspecialchars_ent($this->wikka->page['body']),
                T_("Re-edit this old revision"),
                $this->wikka->FormClose()
            );
        }
        
        return sprintf($format,
            $revision_header,
            $revision_message,
            $show_formatting_form,
            $edit_revision_form
        );
    }
    
    public function format_raw_page_content() {
        $format = <<<XHTML
            <div class="wikisource">
                %s
            </div>
XHTML;
        return sprintf($format,
            nl2br($this->wikka->htmlspecialchars_ent(
                $this->wikka->page["body"],
                ENT_QUOTES
            ))
        );
    }
    
    public function format_page_content() {
        return $this->wikka->Format($this->wikka->page['body'], 'wakka', 'page');
    }
    
    /*
     * Comment Format Methods
     */
    public function format_comments($comments) {
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
    
    public function format_threaded_comment_list($comments) {
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
    
    public function format_comment_list($comments) {
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
    
    public function format_comment_header() {
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
            $comment_count = $this->wikka->CountComments($this->page_tag);
            $display_mode = $this->get_preferred_comment_display_mode();
            $header_title = $this->format_comment_count($comment_count);
            
            if ( $comment_count < 1 ) {
                if ( $this->wikka->HasAccess('comment_post') ) {
                    $comment_form = $this->format_comment_form();
                }
            }
            else {
                $params = sprintf('show_comments=%s#comments', $display_mode);
                $label = ($comment_count == 1) ? T_("Show comment") : T_("Show comments");
                $display_link = sprintf('[<a href="%s">%s</a>]',
                    $this->wikka->Href('', '', $params),
                    $label
                );
            }
        }
        else {
            $header_title = T_("Comments");
            $display_link = sprintf('[<a href="%s">%s</a>]',
                $this->wikka->Href('', '', 'show_comments=0'),
                T_("Hide comments")
            );
            
            if ( $this->wikka->HasAccess('comment_post') ) {
                $comment_form = $this->format_comment_form();
            }
        }

        return sprintf($format, $header_title, $display_link, $comment_form);
    }
    
    public function format_threaded_comment($comment) {
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
        $comment_author = $this->wikka->FormatUser($comment['user']);
        $comment_byline = T_("Comment by ") . $comment_author;
        $comment_ts = sprintf("%s", $comment['time']);
        
        if ( $this->wikka->HasAccess('comment_post') ) {
            $comment_action = $this->format_comment_action($comment);
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
    
    public function format_comment($comment) {
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
        $open_form_tag = $this->wikka->FormOpen("processcomment", "", "post",
            "", "", FALSE, "#comments");
        $submit_value = T_("New Comment");
        $close_form_tag = $this->wikka->FormClose();
        
        return sprintf($format, $open_form_tag, $submit_value, $close_form_tag);
    }
    
    public function format_deleted_comment($comment, $is_threaded=false) {
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
    
    public function format_comment_action($comment) {
        $format = <<<XHTML
                        <div class="commentaction">
                            %s
                                <input type="hidden" name="comment_id" value="%s" />
                                <input type="submit" name="submit" value="%s" />
                                %s
                            %s
                        </div>
XHTML;
        $open_form_tag = $this->wikka->FormOpen("processcomment","","post",
            "","",FALSE,"#comments");
        $comment_id = $comment['id'];
        $submit_button_label = T_("Reply");
        $delete_button = '';
        $close_form_tag = $this->wikka->FormClose();
        
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
