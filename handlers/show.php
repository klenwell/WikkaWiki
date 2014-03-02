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

class ShowHandler {
    
    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html';
    
    # Template
    public $template = <<<HTML
<div id="content%s">
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
        if ( ! $this->request_is_valid() ) {
            return $this->show_error();
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
            return $this->show_error($error);
        }
        
        if ( ! $this->page_is_set() ) {
            $create_link = sprintf('<a href="%s">%s</a>',
                $this->wikka->Href('edit'),
                T_("create"));
            
            $this->error = sprintf("<p>%s</p>\n",
                sprintf(
                    T_("This page doesn't exist yet. Maybe you want to %s it?"),
                    $createlink
                )
            );
            return $this->show_error($error);
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
            ($this->wikka->page['latest'] == 'N');
    }
    
    /*
     * Status Methods
     */
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
    public function show_error() {
        $this->page_content = sprintf('<div class="handler-error">%s</div>',
            $this->error);
        return $this->format_content();
    }
    
    public function format_content() {
        return sprintf($this->template,
            $this->double_click_edit,
            $this->revision_info,
            $this->page_content,
            $this->comments);
    }
    
    public function format_revision_info() {
        trigger_error('in dev', E_USER_ERROR);
    }
    
    public function format_raw_page_content() {
        trigger_error('in dev', E_USER_ERROR);
    }
    
    public function format_page_content() {
        trigger_error('in dev', E_USER_ERROR);
    }
    
    public function format_comments() {
        trigger_error('in dev', E_USER_ERROR);
    }
}
