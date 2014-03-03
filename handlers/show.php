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
 * @package		Handlers
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
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
    public $comments = '';
    
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
            $this->comments = $this->format_comments();
        }
        
        return $this->format_content();
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
        $user = $this->wikka->GetUser();
        return $user && ($user['doubleclickedit'] == 'Y') &&
            ($this->wikka->HasAccess('write'));
    }
     
    public function raw_page_requested() {
        # TODO(klenwell): make this less insane.
        # (bool) works as expected with '0' and '1'
        return (! empty($_GET['raw'])) &&
            ((bool) $this->wikka->GetSafeVar('raw', 'get'));
    }
    
    public function show_comments() {
        return ($this->wikka->GetConfigValue('hide_comments') != 1) &&
			$this->wikka->HasAccess('comment_read');
    }
    
    /*
     * Format Methods
     */
    public function format_content() {
        return sprintf($this->template,
            $this->double_click_edit,
            $this->revision_info,
            $this->page_content,
            $this->comments);
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
    
    public function format_comments() {
        trigger_error('TODO: implement comments as a library module', E_USER_NOTICE);
    }
}
