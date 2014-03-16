<?php
/**
 * Install Handler
 *
 * Handles all stages of install process:
 *  1. Configuration Form
 *  2. Configuration Summary
 *  3. Write Config File
 *  4. Write Config Summary
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
require_once('wikka/request.php');
require_once('wikka/response.php');


class InstallHandler extends WikkaHandler {
    
    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html; charset=utf-8';
    
    private $states = array('intro',
                            'form',
                            'install',
                            'write_files'); 
    private $state = '';
    
    # Template
    public $template = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    %s
  </head>
  <body>
    <div class="header">
      %s
    </div>
    <div id="content">
      %s
    </div>
    <div id="footer">
      %s
    </div>
  </body>
</html>
HTML;

    # Template Vars (%s from template above in order)
    protected $head = '<!-- head not set -->';
    protected $header = '<!-- header not set -->';
    protected $stage_content = '<!-- content not set -->';
    protected $footer = '<!-- footer not set -->';

    /*
     * Main Handler Method
     */
    public function handle() {
        $this->request = new WikkaRequest();
        
        # Simple state machine
        $method = $this->set_state();
        $content = $this->$method();
        
        # Return response
        $response = new WikkaResponse($content);
        $response->status = 200;
        $response->set_header('Content-Type', $this->content_type);
        return $response;
    }
        
    /*
     * State Methods
     * Each state method should set $this->header, $this->stage_content,
     * and $this->footer, then call $this->format_content and return the
     * result.
     */
    private function set_state() {
        #
        # Returns method name for next state
        # Side-effects: sets $this->state
        #
        $requested_stage = $this->request->get_post_var('next-stage');
        
        if ( in_array($requested_stage, $this->states) ) {
            $this->state = $requested_stage;
        }
        else {
            $this->state = 'intro';
        }
        
        return sprintf('state_%s', $this->state);
    }
    
    private function state_intro() {
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_intro();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_form() {
        throw new Exception('TODO: state_form');
    }
    
    private function state_install() {
        throw new Exception('TODO: state_install');
    }
    
    private function state_write_files() {
        throw new Exception('TODO: state_write_files');
    }
    
    /*
     * Private Methods
     */
    private function is_upgrade() {
        return (bool) $this->wikka->GetConfigValue('wakka_version');
    }
    
    private function is_fresh_install() {
        return !($this->is_upgrade());
    }
     
    /*
     * Format Methods
     */
    protected function format_content() {
        return sprintf($this->template,
            $this->head,
            $this->header,
            $this->stage_content,
            $this->footer
        );
    }
    
    protected function format_head() {
        $head_f = <<<XHTML
	<title>Wikka Installation</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="keywords" content="Wikka Wakka Wiki" />
	<meta name="description" content="Wikka Wiki Install" />
	<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="templates/install/install.css?%s" />
XHTML;

        $hash = $this->wikka->htmlspecialchars_ent(
            $this->wikka->GetConfigValue('stylesheet_hash'));

        return sprintf($head_f, $hash);
    }
    
    protected function format_header() {
        $header_f = <<<XHTML
    <h1>WikkaWiki <span class="type">%s</span></h1>
XHTML;

        $type = ( $this->is_upgrade() ) ? 'Upgrade' : 'Install';
        return sprintf($header_f, $type);

    }
    
    protected function format_footer() {
        return '';
    }
    
    protected function format_intro() {
        $intro_f = <<<XHTML
    <div class="intro">
      %s
    </div>
    %s
XHTML;

        if ( $this->is_upgrade() ) {
            $inner_f = <<<XHTML
      Your installed Wikka is reporting itself as <tt>%s</tt>. You are about to
      <strong>upgrade</strong> to Wikka version <tt>%s</tt>. To start the
      upgrade, please hit the start button below.
XHTML;
            $intro = sprintf($inner_f,
                $this->wikka->GetConfigValue('wakka_version'),
                WAKKA_VERSION
            );
        }
        else {
            $inner_f = <<<XHTML
      Since there is no existing Wikka configuration file, this probably is a 
      fresh Wikka install. You are about to install Wikka <tt>%s</tt>.
      Installing Wikka will take only a few minutes. To start the installation,
      please hit the start button below.
XHTML;
            $intro = sprintf($inner_f,
                WAKKA_VERSION
            );
        }

        return sprintf($intro_f, $intro, $this->next_stage_button('form', 'Start'));
    }
    
    private function next_stage_button($stage, $label='Continue') {
        $form_f = <<<XHTML
    <div class="form-controls">
      %s
        <input type="submit" name="submit" value="%s" />
        <input type="hidden" name="next-stage" value="%s" />
      </form>
    </div>
XHTML;

        return sprintf($form_f, $this->wikka->FormOpen(), $label, $stage);
    }
}
