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
require_once('libs/install/installer.php');
require_once('libs/install/migrator.php');


class InstallHandler extends WikkaHandler {
    
    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html; charset=utf-8';
    
    # Webservice resources
    public $request = null;
    public $config = null;
    
    # States
    private $states = array('intro',
                            'database_form',
                            'wiki_settings_form',
                            'admin_form',
                            'install',
                            'upgrade',
                            'save_config_file',
                            'conclusion'); 
    private $state = 'intro';
    
    # Form errors
    private $form_errors = array();
    
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
    public function handle($webservice) {
        $this->request = $webservice->request;
        $this->config = $webservice->config;
        
        # Load config from session
        $_SESSION['install'] = (isset($_SESSION['install'])) ?
            $_SESSION['install'] : array();
            
        if ( ! empty($_SESSION['install']['config']) ) {
            $this->config = $_SESSION['install']['config'];
        }
        else {
            $_SESSION['install']['config'] = $this->config;
        }
        
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
     * Public Methods
     */
    public function next_stage_button($stage, $label='Continue', $class='primary') {
        $form_f = <<<XHTML
      %s
        <input type="submit" class="btn btn-%s" name="submit" value="%s" />
        <input type="hidden" name="next-stage" value="%s" />
      </form>
XHTML;

        return sprintf($form_f, $this->wikka->FormOpen(), $class, $label, $stage);
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
            return $this->change_state('wiki_settings_form');
        }
        
        $form_submitted = ($this->request->get_post_var('form-submitted') == 'database');
        if ( $form_submitted ) {
            $form_values = $this->request->get_post_var('config', array());
            $this->config = array_merge($this->config, $form_values);
            
            if ( $this->validate_database_values() ) {
                # Update Session data
                $_SESSION['install']['config'] = $this->config;

                # Change State
                return $this->change_state('wiki_settings_form');
            };
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
        # Process form request
        $form_submitted = (
            $this->request->get_post_var('form-submitted') == 'wiki-settings');
        if ( $form_submitted ) {
            $form_values = $this->request->get_post_var('config', array());
            $this->config = array_merge($this->config, $form_values);
            
            if ( $this->validate_wiki_settings_values() ) {
                # Update Session data
                $_SESSION['install']['config'] = $this->config;

                # Change State
                if ( $this->is_upgrade() ) {
                  return $this->change_state('upgrade');
                }
                else {
                  return $this->change_state('admin_form');
                }
            };
        }

        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_wiki_settings_form();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_admin_form() {
        # Process form request
        $form_submitted = (
            $this->request->get_post_var('form-submitted') == 'admin-form');
        if ( $form_submitted ) {
            $form_values = $this->request->get_post_var('config', array());
            $this->config = array_merge($this->config, $form_values);
            
            if ( $this->validate_admin_values() ) {
                # Update Session data
                $_SESSION['install']['config'] = $this->config;

                # Change State
                return $this->change_state('install');
            };
        }
      
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_admin_form();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_install() {
        $installer = new WikkaInstaller($_SESSION['install']['config']);
        $installer->install_wiki();
        
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_installer_report($installer);
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_upgrade() {
        $old_version = $this->config['wakka_version'];
        $new_version = WAKKA_VERSION;
      
        $migrator = new WikkaMigrator(WIKKA_MIGRATIONS_FILE_PATH);
        $migrator->report_section_header(
            sprintf('Start migration from version %s to %s', $old_version, $new_version));
        $migrator->run_migrations($old_version, $new_version);
        
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_installer_report($migrator);
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    private function state_save_config_file() {
        $do_config_dir_check = $this->request->get_post_var('submit') != 'Continue';
      
        if ( $do_config_dir_check && ! is_writeable('config') ) {
            $e = new ConfigDirWriteError('config directory not writeable');
            $this->stage_content = $this->format_write_config_error($e);
        }
        else {
            # Attempt to write config file
            try {
                $installer = new WikkaInstaller($this->config);
                $installer->write_config_file();
                return $this->change_state('conclusion');
            }
            catch (ConfigFileWriteError $e) {
                $this->stage_content = $this->format_write_config_error($e);
            }
        }
        
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }

    private function state_conclusion() {
        $_SESSION['install']['config'] = NULL;
        
        # Set template variables
        $this->head = $this->format_head();
        $this->header = $this->format_header();
        $this->stage_content = $this->format_conclusion();
        $this->footer = $this->format_footer();
        
        # Return output
        $content = $this->format_content();
        return $content;
    }
    
    /*
     * Validators
     */
    private function validate_database_values() {
        if ( empty($this->config['mysql_host']) ) {
            $this->form_errors['mysql_host'] = 'Please input a valid MySQL host';
        }
        
        if ( empty($this->config['mysql_database']) ) {
            $this->form_errors['mysql_database'] = 'Please input a valid database';
        }
        
        if ( empty($this->config['mysql_user']) ) {
            $this->form_errors['mysql_user'] = 'Please input a valid MySQL username';
        }
        
        if ( empty($this->config['mysql_password']) ) {
            $this->form_errors['mysql_password'] = 'Please input a valid MySQL password';
        }
        
        if ( $this->form_errors ) {
            return FALSE;
        }
        
        # Test connection
        try {
            $host = $this->config['mysql_host'];
            $name = $this->config['mysql_database'];
            $user = $this->config['mysql_user'];
            $pass = $this->config['mysql_password'];
            $dsn = sprintf('mysql:host=%s;dbname=%s', $host, $name);
            $pdo = new PDO($dsn, $user, $pass);
            return TRUE;
        }
        catch (Exception $e) {
            $this->form_errors['database_form'] = sprintf(
                "<h4>%s</h4>Message: %s",
                "Failed to connect to database. Please check settings.",
                $e->getMessage()
            );
            return FALSE;
        }
    }
    
    private function validate_wiki_settings_values() {
        if ( empty($this->config['wakka_name']) ) {
            $this->form_errors['wakka_name'] = "Please fill in a title " .
              "for your wiki. For example: <em>My Wikka website</em>";
        }
        
        if ( empty($this->config['root_page']) ||
             ! preg_match('/^[A-Za-z0-9]{3,}$/', $this->config['root_page'])) {
            $this->form_errors['root_page'] = "Please fill in a valid name " .
              "for your wiki's homepage. For example: <em>start</em> or " .
              "<em>HomePage</em>";
        }
        
        if ( $this->form_errors ) {
            return FALSE;
        }
        else {
          return TRUE;
        }
    }
    
    private function validate_admin_values() {
        $admin_user = $this->get_form_value('admin_users');
        if ( empty($admin_user) ) {
            $this->form_errors['admin_users'] = "Please fill in an admin name.";
        }
        elseif ( ! preg_match('/^[A-Z][a-z]+[A-Z0-9][A-Za-z0-9]*$/', $admin_user) ) {
            $this->form_errors['admin_users'] = "Admin name must be formatted " .
                "as a WikiName. For example: <em>JohnSmith</em> or " .
                "<em>AbC</em> or <em>Ted22</em>";
        }
        
        $pass1 = $this->get_form_value('password');
        $pass2 = $this->get_form_value('password2');
        if ( ! $pass1 ) {
            $this->form_errors['password'] = "Please fill in a password.";
        }
        elseif ( strlen($pass1) < 5 ) {
            $this->form_errors['password'] = "Please fill in a password.";
        }
        
        if ( ! $pass2 ) {
            $this->form_errors['password2'] = "Please fill in a password.";
        }
        elseif ( strcmp($pass1, $pass2) != 0 ) {
            $this->form_errors['password2'] = "Passwords don't match.";
        }
        
        $admin_email = $this->get_form_value('admin_email');
        if ( empty($admin_email) ) {
            $this->form_errors['admin_email'] = "Please fill in your email address.";
        }
        elseif ( ! preg_match("/^[A-Za-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[A-Za-z0-9.-]+$/i",
            $admin_email) ) {
            $this->form_errors['admin_email'] = "Please fill in a valid email address.";
        }
        
        if ( $this->form_errors ) {
            return FALSE;
        }
        else {
          return TRUE;
        }
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
    
    private function run_migrations() {
    }
    
    private function get_config_value($key, $default='') {
        if ( isset($this->config[$key]) ) {
            return $this->config[$key];
        }
        else {
            return $default;
        }
    }
    
    private function get_form_value($key, $default='') {
        $form_values = $this->request->get_post_var('config', array());
        
        if ( isset($form_values[$key]) ) {
            return $form_values[$key];
        }
        else {
            return $default;
        }
    }
    
    private function get_theme_options() {
        #
        # Return an array of theme options pulled from templates dir. Array
        # will consist of label => value pairs.
        #
        $options = array();
        
        # Use configured path
        $theme_dir = 'templates';
        $dp = opendir($theme_dir);
        
        # Build options list
        while ( $f = readdir($dp) ) {
            if ( $f[0] == '.' ) {
                continue;
            }
            else {
                $label = ucwords($f);
                $options[$label] = $f;
            }
        }
        
        return $options;
    }
    
    private function get_language_options() {
        #
        # Return an array of language options pulled from lang dir. Array
        # will consist of label => value pairs.
        #
        $options = array();
        
        $lang_map = array(
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'vn' => 'Tiếng Việt'
        );
        
        # Use configured path
        $lang_dir = 'lang';
        $dp = opendir($lang_dir);
        
        # Build options list
        $path_f = 'lang%s%s%s%s.inc.php';
        while ( $f = readdir($dp) ) {
            $path = sprintf($path_f, DIRECTORY_SEPARATOR, $f,
                DIRECTORY_SEPARATOR, $f);
            
            if ( $f[0] == '.' ) {
                continue;
            }
            elseif ( file_exists($path) ) {
                $label = isset($lang_map[$f]) ? $lang_map[$f] : $f;
                $options[$label] = $f;
            }
        }
        
        return $options;
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
      <div class="preamble">
        %s
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
$ touch %s ; chmod -v 666 %s</pre>
          <p>
            If the installer is unable to write to the necessary files, you
            will receive a warning during the %s process.
          </p>
          <p>
            For additional information, see the <a href="%s"
            target="_blank">documentation</a>.
          </p>
        </div>
      </div>
    </div>
XHTML;

        if ( $this->is_upgrade() ) {
            $preamble_f = <<<XHTML
        <p>
          Your current version of Wikka is <code>%s</code>. You are 
          about to <strong>upgrade</strong> to Wikka version <code>%s</code>. To 
          start the upgrade, please hit the
          <span class="label label-primary">start</span> button.
        </p>
XHTML;
            $preamble = sprintf($preamble_f,
                $this->wikka->GetConfigValue('wakka_version'),
                WAKKA_VERSION
            );
            $type = 'upgrade';
            $button = $this->next_stage_button('wiki_settings_form', 'Start Upgrade');
        }
        else {
            $preamble_f = <<<XHTML
        <p>
          Since there is no existing Wikka configuration file, this probably is a 
          fresh Wikka install. You are about to install Wikka <code>%s</code>.
          Installing Wikka will take only a few minutes. To start the installation,
          please hit the <span class="label label-primary">start</span> button.
        </p>
XHTML;
            $preamble = sprintf($preamble_f,
                WAKKA_VERSION
            );
            $type = 'install';
            $button = $this->next_stage_button('database_form', 'Start Install');
        }

        return sprintf($intro_f,
            $preamble,
            $button,
            WIKKA_CONFIG_PATH, WIKKA_CONFIG_PATH,
            $type, WIKKA_INSTALL_DOCS_URL);
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
          %s
        </fieldset>

        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <input type="submit" class="btn btn-primary" name="submit" value="Submit" />
            <input type="hidden" name="next-stage" value="database_form" />
            <input type="hidden" name="form-submitted" value="database" />
          </div>
        </div>
      </form>
    </div>
XHTML;

        # Form-level errors
        if ( isset($this->form_errors['database_form']) ) {
            $form_alert = sprintf('<div class="alert alert-danger">%s</div>',
                $this->form_errors['database_form']
            );
        }
        elseif ( $this->form_errors ) {
            $form_alert = sprintf('<div class="alert alert-danger">%s</div>',
                'There were errors with your submission'
            );
        }
        else {
            $form_alert = '';
        }

        # Build form groups
        $db_host_help = 'The host your MySQL server is running on. Usually ' .
            '"localhost" (i.e., the same machine your Wikka site is on).';
        $db_host_group = $this->build_input_form_group('mysql_host',
            'MySQL Host', $this->get_config_value('mysql_host'), $db_host_help);
        
        $db_name_help = 'The MySQL database Wikka should use. This database ' .
            '<strong class="text-danger">needs to exist already</strong> ' .
            'before you continue!';
        $db_name_group = $this->build_input_form_group('mysql_database',
            'MySQL Database', $this->get_config_value('mysql_database'), $db_name_help);
        
        $db_user_group = $this->build_input_form_group('mysql_user',
            'MySQL User Name', $this->get_config_value('mysql_user'));
        
        $db_pass_help = 'Name and password of the MySQL user used to connect ' .
            'to your database.';
        $db_pass_group = $this->build_input_form_group('mysql_password',
            'MySQL Password', $this->get_config_value('mysql_password'),
            $db_pass_help, 'password');
        
        $db_prefix_help = 'Prefix of all tables used by Wikka. This allows you ' .
            'to run multiple Wikka installations using the same MySQL database ' .
            'by configuring them to use different table prefixes.';
        $db_prefix_group = $this->build_input_form_group('table_prefix',
            'Table Prefix', $this->get_config_value('table_prefix'), $db_prefix_help);
        
        return sprintf($form_f,
            $this->wikka->FormOpen(),
            $form_alert,
            $db_host_group,
            $db_name_group,
            $db_user_group,
            $db_pass_group,
            $db_prefix_group
        );
    }
    
    protected function format_wiki_settings_form() {
        $form_f = <<<XHTML
    <div class="form">
      %s
        <fieldset>
          <h4>Wiki Configuration</h4>
          %s
          %s
          %s
          %s
          %s
          %s
          %s
          %s
          %s
        </fieldset>

        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <input type="submit" class="btn btn-primary" name="submit" value="Submit" />
            <input type="hidden" name="next-stage" value="wiki_settings_form" />
            <input type="hidden" name="form-submitted" value="wiki-settings" />
          </div>
        </div>
      </form>
    </div>
XHTML;

        # Form-level errors
        if ( $this->form_errors ) {
            $form_alert = sprintf('<div class="alert alert-danger">%s</div>',
                'There were errors with your submission'
            );
        }
        else {
            $form_alert = '';
        }
        
        # Build form groups
        $wakka_name_help = 'The name of your wiki, as it will be displayed ' .
            'in the title.';
        $wakka_name_group = $this->build_input_form_group('wakka_name',
            "Your Wiki's Name", $this->get_config_value('wakka_name'),
            $wakka_name_help);
        
        $root_page_help = 'Your wiki\'s home page. It should not contain ' .
            'any space or special character and be at least 3 characters ' .
            'long. It is typically formatted as a <abbr title="A WikiName ' .
            'is formed by two or more capitalized words without space, ' .
            'e.g. HomePage">WikiName</abbr>.';
        $root_page_group = $this->build_input_form_group('root_page',
            'Home Page', $this->get_config_value('root_page'), $root_page_help);
        
        $wiki_suffix_help = 'Suffix used for cookies and part of the session ' .
            'name. This allows you to run multiple Wikka installations on the ' .
            'same server by configuring them to use different wiki prefixes.';
        $wiki_suffix_group = $this->build_input_form_group('wiki_suffix',
            "Your Wiki Suffix", $this->get_config_value('wiki_suffix'),
            $wiki_suffix_help);
        
        $meta_help = 'Optional keywords/description to insert into the HTML meta headers.';
        $meta_keywords_group = $this->build_input_form_group('meta_keywords',
            'Meta Keywords', $this->get_config_value('meta_keywords'));
        $meta_desc_group = $this->build_input_form_group('meta_description',
            'Meta Description', $this->get_config_value('meta_description'),
            $meta_help);
        
        $theme_help = "Choose the <em>look and feel</em> of your wiki " .
            "(you'll be able to change this later).";
        $theme_group = $this->build_select_group('theme',
            'Theme', $this->get_theme_options(),
            $this->get_config_value('theme'));
        $lang_group = $this->build_select_group('default_lang',
            'Language Pack', $this->get_language_options(),
            $this->get_config_value('default_lang'), $theme_help);
        
        if ( $this->is_upgrade() ) {
            $version_group = $this->build_version_group();
        }
        else {
           $version_group = '';
        }
        
        return sprintf($form_f,
            $this->wikka->FormOpen(),
            $form_alert,
            $wakka_name_group,
            $root_page_group,
            $wiki_suffix_group,
            $meta_keywords_group,
            $meta_desc_group,
            $theme_group,
            $lang_group,
            $version_group
        );
    }
    
    protected function format_admin_form() {
        $form_f = <<<XHTML
    <div class="form">
      %s
        <fieldset>
          <h4>Administrative Account Configuration</h4>
          %s
          %s
          %s
          %s
          %s
          %s
        </fieldset>

        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <input type="submit" class="btn btn-primary" name="submit" value="Submit" />
            <input type="hidden" name="next-stage" value="admin_form" />
            <input type="hidden" name="form-submitted" value="admin-form" />
          </div>
        </div>
      </form>
    </div>
XHTML;

        # Form-level errors
        if ( $this->form_errors ) {
            $form_alert = sprintf('<div class="alert alert-danger">%s</div>',
                'There were errors with your submission'
            );
        }
        else {
            $form_alert = '';
        }
        
        # Build form groups
        $admin_name_help = 'This is the username of the person running this ' .
            'wiki. Later you\'ll be able to add other admins. The admin ' .
            'username should be formatted as a <abbr title="A WikiName is ' .
            'formed by two or more capitalized words without space, ' .
            'e.g. JohnDoe">WikiName</abbr>.';
        $admin_name_group = $this->build_input_form_group('admin_users',
            "Admin Name", $this->get_config_value('admin_users'),
            $admin_name_help);
        
        $pass_help = "Choose a password for the wiki administrator (5+ chars)";
        $pass1 = $this->get_form_value('password');
        $pass2 = $this->get_form_value('password2');
        $pass1_group = $this->build_input_form_group('password', "Enter Password",
            $pass1, '', 'password');
        $pass2_group = $this->build_input_form_group('password2',
            "Confirm Password", $pass2, $pass_help, 'password');
        
        $admin_email_help = "Administrator's email address.";
        $admin_email_group = $this->build_input_form_group('admin_email',
            "Email", $this->get_config_value('admin_email'),
            $admin_email_help);
        
        return sprintf($form_f,
            $this->wikka->FormOpen(),
            $form_alert,
            $admin_name_group,
            $pass1_group,
            $pass2_group,
            $admin_email_group,
            $this->build_version_group()
        );
    }
    
    protected function format_installer_report($installer) {
        $report_f = <<<XHTML
    <div class="installer-report">
      <div class="container">
        %s
      </div>
      
      %s
      <div class="form-group buttons">
        %s
        %s
      </div>
    </div>
XHTML;

        # Error handling
        if ( $installer->errors ) {
            $restart_button_f = <<<XHTML
        <div class="pull-left">
          %s
        </div>
XHTML;

            $restart_button = sprintf($restart_button_f,
                $this->next_stage_button('database_form', 'Try Again', 'warning'));

            $warning = <<<XHTML
      <div class="panel panel-warning">
        <div class="panel-heading">
          <h4 class="panel-title">Install Issues Reported</h4>
        </div>
        <div class="panel-body">
          <p>
            There were some issues reported during the install process. This
            does not necessarily mean the install was unsuccessful. Please
            review the issues reported above in red.
          </p>
          <p>
            If you believe the issues are negligible, hit the
            <span class="label label-primary">Continue</span> button.
            Otherwise, you can press the <span class="label label-warning">
            Try Again</span> button to resubmit your information and try again. 
          </p>
        </div>
      </div>
XHTML;
        }
        else {
            $restart_button = '';
            $warning = '';
        }
        # End error handling

        return sprintf($report_f,
            implode("\n", $installer->report),
            $warning,
            $restart_button,
            $this->next_stage_button('save_config_file')
        );
    }
    
    protected function format_write_config_error($e) {
        $format = <<<XHTML
    <div class="installer-error row">
      <h4 class="alert alert-danger">
        <span class="glyphicon glyphicon-exclamation-sign"></span>
        Configuration issue
      </h4>
      
      <div class="problem">
        <h3>Problem</h3>
        <h4>%s</h4>
      </div>
      
      <div class="solution">
        <h3>Solution</h3>
        %s
      </div>
    </div>
XHTML;

        return sprintf($format, $e->getMessage(), $e->render_solution($this));
    }
    
    protected function format_conclusion() {
        $conclusion_f = <<<XHTML
    <div class="conclusion">
      <h4 class="alert alert-success">
        <span class="glyphicon glyphicon-ok"></span>
        Congratulations! Your installation is complete.
      </h4>
      
      <h4 class="next">
        To return to you wikka site, click this link: <a href="%s">Home Page</a>
      </h4>
      
      <div class="panel panel-warning">
        <div class="panel-heading">
          <h4 class="panel-title">Permissions Reminder</h4>
        </div>
        <div class="panel-body">
          <p>
            Don't forget to remove write access from your configuration files:
          </p>
          <pre>$ chmod -v 755 config
$ chmod -v 644 wikka.config.php</pre>
        </div>
      </div>
    </div>
XHTML;

        return sprintf($conclusion_f, WIKKA_BASE_URL);
    }
    
    /*
     * Format Helpers
     */
    private function build_input_form_group($id, $label, $value='', $help_text='',
        $type='text') {
        $html_f = <<<XHTML
          <div id="%s-group" class="row form-group%s">
            <label for="%s" class="col-sm-2 control-label">%s</label>
            <div class="col-sm-5">
              <input id="%s" class="form-control" type="%s"
                name="%s" value="%s" />
              %s
            </div>
          </div>   
XHTML;

        # Check for errors
        if ( isset($this->form_errors[$id]) ) {
            $error_class = ' has-error';
            $help_text = $this->form_errors[$id];
        }
        else {
            $error_class = '';
        }
        
        # Helper Text
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
        
        $name = sprintf('config[%s]', $id);
        
        return sprintf($html_f, $id, $error_class, $id, $label, $id, $type, $name,
            $value, $help_div);
    }
    
    private function build_checkbox_form_group($id, $label, $value,
        $is_checked=FALSE, $help_text='') {
        $html_f = <<<XHTML
          <div id="%s-group" class="row form-group%s">
            <label for="%s" class="col-sm-2 control-label">%s</label>
            <div class="col-sm-5">
              <input id="%s" class="form-control" type="checkbox"
                name="%s" value="%s"%s />
              %s
            </div>
          </div>   
XHTML;

        # Check for errors
        if ( isset($this->form_errors[$id]) ) {
            $error_class = ' has-error';
            $help_text = $this->form_errors[$id];
        }
        else {
            $error_class = '';
        }
        
        # Helper Text
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
        
        # Checked Attr
        $check_attr = ($is_checked) ? ' checked="checked"' : '';
        
        $name = sprintf('config[%s]', $id);
        
        return sprintf($html_f, $id, $error_class, $id, $label, $id, $name,
            $value, $check_attr, $help_div);
    }
    
    private function build_select_group($id, $label, $options, $value='',
        $help_text='') {
        $html_f = <<<XHTML
          <div id="%s-group" class="row form-group%s">
            <label for="%s" class="col-sm-2 control-label">%s</label>
            <div class="col-sm-5">
              <select id="%s" class="form-control" name="%s" >
                %s
              </select>
              %s
            </div>
          </div>   
XHTML;

        # Check for errors
        if ( isset($this->form_errors[$id]) ) {
            $error_class = ' has-error';
            $help_text = $this->form_errors[$id];
        }
        else {
            $error_class = '';
        }

        # Set helper text value
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
        
        # Build option list
        $option_tags = array();
        $option_f = '<option value="%s"%s>%s</option>';
        foreach ($options as $opt_label => $opt_value) {
          $selected = ($opt_value == $value) ? ' selected' : '';
          $option_tags[] = sprintf($option_f, $opt_value, $selected, $opt_label);
        }
        
        $name = sprintf('config[%s]', $id);
        
        return sprintf($html_f, $id, $error_class, $id, $label, $id, $name,
            implode('', $option_tags), $help_div);
    }
    
    private function build_version_group() {
        $group_f = <<<XHTML
          <div class="panel panel-info">
            <div class="panel-heading">
              <h4 class="panel-title">Version Update Check</h4>
            </div>
            <div class="panel-body">
              <p>
                It is <strong>strongly recommended</strong> that you leave this
                option checked if your run your wiki on the internet.
                Administrator(s) will be notified automatically on the wiki if
                a new version of WikkaWiki is available for download. See the
                <a href="http://docs.wikkawiki.org/CheckVersionActionInfo"
                  target="_blank">documentation</a> for details. Please note
                that if you leave this option enabled, your installation will
                periodically contact a WikkaWiki server for update information.
                As a result, your IP address and/or domain name may be recorded
                in our referrer logs.
              </p>
              
              <p>
                %s
              </p>
            </div>
          </div>
XHTML;

        $config_value = $this->get_config_value('enable_version_check', NULL);
        $value_missing = ($config_value == NULL); 
        $is_checked = ( $value_missing || $config_value == '1' );
        $version_group = $this->build_checkbox_form_group('enable_version_check',
            "Enable Check", '1', $is_checked);
        
        return sprintf($group_f, $version_group);
    }
}
