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
                            'database_form',
                            'wiki_settings_form',
                            'admin_form',
                            'install',
                            'upgrade',
                            'conclusion'); 
    private $state = 'intro';
    
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
        $requested_stage = $this->request->get_post_var('next-stage', 'intro');
        $content = $this->change_state($requested_stage);
        
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
    private function change_state($new_state) {
        if ( in_array($new_state, $this->states) ) {
            $method = $this->set_state($new_state);
        }
        else {
            throw new Exception(sprintf("Invalid install state: %s", $new_state));
        }
        
        return $this->$method();
    }
    
    private function set_state($state) {
        #
        # Returns method name for state
        # Side-effects: sets $this->state
        #
        $this->state = $state;
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
    
    private function state_database_form() {
        # Skip stage during upgrade
        if ( $this->is_upgrade() ) {
            return $this->change_state('upgrade');
        }
        
        # Process form request
        if ( $this->request->get_post_var('form-submitted') ) {
            var_dump($_POST);
        }
      
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_database_form();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_wiki_settings_form() {
    }
    
    private function state_admin_form() {
    }
    
    private function state_install() {
        throw new Exception('TODO: state_install');
    }
    
    private function state_upgrade() {
        throw new Exception('TODO: state_upgrade');
        $this->run_migrations();
        $config = $this->update_config();
        $saved = $this->save_config($config);
      
        if ( $saved ) {
            return $this->change_state('conclusion');
        }
        else {
            return $this->format_upgrade_error();
        }
    }

    private function state_conclusion() {
        throw new Exception('TODO: state_conclusion');
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
        
        $button = $this->next_stage_button('database_form', 'Start');

        return sprintf($intro_f, $intro, $type, $button);
    }
    
    protected function format_database_form() {
        $form_f = <<<XHTML
    <div class="form">
      %s
        <fieldset>
          <h4>Database Configuration</h4>
          %s
          %s
          %s
          %s
          %s
        </fieldset>

        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <input type="submit" class="btn btn-primary" name="submit" value="Submit" />
            <input type="hidden" name="next-stage" value="form" />
            <input type="hidden" name="form-submitted" value="true" />
          </div>
        </div>
      </form>
    </div>
XHTML;

        # Build form groups
        $db_host_help = 'The host your MySQL server is running on. Usually ' .
            '"localhost" (i.e., the same machine your Wikka site is on).';
        $db_host_group = $this->build_input_form_group('mysql-host',
            'config[mysql_host]', 'MySQL Host',
            $this->wikka->GetConfigValue('mysql_host'), $db_host_help);
        
        $db_name_help = 'The MySQL database Wikka should use. This database ' .
            '<strong class="text-danger">needs to exist already</strong> ' .
            'before you continue!';
        $db_name_group = $this->build_input_form_group('mysql-database',
            'config[mysql_database]', 'MySQL Database',
            $this->wikka->GetConfigValue('mysql_database'), $db_name_help);
        
        $db_user_group = $this->build_input_form_group('mysql-user',
            'config[mysql_user]', 'MySQL User Name',
            $this->wikka->GetConfigValue('mysql_user'));
        
        $db_pass_help = 'Name and password of the MySQL user used to connect ' .
            'to your database.';
        $db_pass_group = $this->build_input_form_group('mysql-password',
            'config[mysql_password]', 'MySQL Password',
            $this->wikka->GetConfigValue('mysql_password'), $db_pass_help, 'password');
        
        $db_prefix_help = 'Prefix of all tables used by Wikka. This allows you ' .
            'to run multiple Wikka installations using the same MySQL database ' .
            'by configuring them to use different table prefixes.';
        $db_prefix_group = $this->build_input_form_group('table-prefix',
            'config[table_prefix]', 'Table Prefix',
            $this->wikka->GetConfigValue('table_prefix'), $db_prefix_help);
        
        return sprintf($form_f,
            $this->wikka->FormOpen(),
            $db_host_group,
            $db_name_group,
            $db_user_group,
            $db_pass_group,
            $db_prefix_group
        );
    }
    
    private function next_stage_button($stage, $label='Continue') {
        $form_f = <<<XHTML
    <div>
      %s
        <input type="submit" class="btn btn-primary" name="submit" value="%s" />
        <input type="hidden" name="next-stage" value="%s" />
      </form>
    </div>
XHTML;

        return sprintf($form_f, $this->wikka->FormOpen(), $label, $stage);
    }
    
    private function build_input_form_group($id, $name, $label, $value='',
        $help_text='', $type='text') {
        $html_f = <<<XHTML
          <div class="row form-group">
            <label for="%s" class="col-sm-2 control-label">%s</label>
            <div class="col-sm-5">
              <input id="%s" class="form-control" type="%s"
                name="%s" value="%s" />
              %s
            </div>
          </div>   
XHTML;

        if ( $help_text ) {
            $help_div_f = <<<XDIV
              <span class="help-block">
                %s
              </span>
XDIV;
            $help_div = sprintf($help_div_f, $help_text);
        }
        else {
            $help_div = '';
        }
        
        return sprintf($html_f, $id, $label, $id, $type, $name, $value, $help_div);
    }
}
