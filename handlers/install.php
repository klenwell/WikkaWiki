<?php
/**
 * Install Handler
 *
 * Handles all stages of install process:
 *  1. Intro
 *  2. Form
 *  3. Install
 *  4. Write Files
 *  5. Conclusion
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
                            'write_files',
                            'conclusion'); 
    private $state = '';
    
    # Template
    # http://getbootstrap.com/getting-started/#template
    # http://getbootstrap.com/examples/sticky-footer-navbar/
    public $template = <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Wikka Installation</title>
    <meta name="keywords" content="Wikka Wiki" />
    <meta name="description" content="WikkaWiki Install" />
    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    
    %s

    <!-- Bootstrap -->
    <link rel="stylesheet"
      href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />
    <link rel="stylesheet" href="templates/install/bootstrap-override.css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  
  <body>
    <div class="container">
      <div class="page-header">
        %s
      </div>
      <div class="content">
        %s
      </div>
    </div>

    <div id="footer">
      <div class="container">
        %s
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
    <link rel="stylesheet" href="templates/install/bootstrap-override.css?%s" />
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
        return <<<XHTML
        <p class="text-muted">WikkaWiki</p>
XHTML;
    }
    
    protected function format_intro() {
        $intro_f = <<<XHTML
    <div class="intro">
      <div class="lead">
        %s
      </div>
      
      <div class="panel panel-info">
        <div class="panel-heading">
          <h4 class="panel-title">Permissions Note</h4>
        </div>
        <div class="panel-body">
          <p>
            This installer will try to write some configuration files to your
            Wikka directory. In order for this to work, you must make sure the
            web server has write access to the necessary files. You can handle
            this in advance by running the following commands from the command
            line:
          </p>
          <pre>$ chmod -v 777 config
$ touch /var/www/ww-git/wikka.config.php ; chmod 666 /var/www/ww-git/wikka.config.php</pre>
          <p>
            If the installer is unable to write to the necessary files, you
            will receive a warning during the %s process.
          </p>
          <p>
            For additional information, see the <a
              href="http://docs.wikkawiki.org/WikkaInstallation"
              target="_blank">documentation</a>.
          </p>
        </div>
      </div>
    </div>
    %s
XHTML;

        if ( $this->is_upgrade() ) {
            $inner_f = <<<XHTML
        <p>
          Your installed Wikka is reporting itself as <code>%s</code>. You are 
          about to <strong>upgrade</strong> to Wikka version <code>%s</code>. To 
          start the upgrade, please hit the start button below.
        </p>
XHTML;
            $intro = sprintf($inner_f,
                $this->wikka->GetConfigValue('wakka_version'),
                WAKKA_VERSION
            );
            $type = 'upgrade';
        }
        else {
            $inner_f = <<<XHTML
        <p>
          Since there is no existing Wikka configuration file, this probably is a 
          fresh Wikka install. You are about to install Wikka <code>%s</code>.
          Installing Wikka will take only a few minutes. To start the installation,
          please hit the start button below.
        </p>
XHTML;
            $intro = sprintf($inner_f,
                WAKKA_VERSION
            );
            $type = 'install';
        }

        return sprintf($intro_f, $intro, $type, $this->next_stage_button('form', 'Start'));
    }
    
    private function next_stage_button($stage, $label='Continue') {
        $form_f = <<<XHTML
    <div>
      %s
        <input type="submit" name="submit" value="%s" />
        <input type="hidden" name="next-stage" value="%s" />
      </form>
    </div>
XHTML;

        return sprintf($form_f, $this->wikka->FormOpen(), $label, $stage);
    }
}
