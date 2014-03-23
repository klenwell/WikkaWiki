<?php
/**
 * libs/install/installer.php
 *
 * WikkaInstaller handles system updates for InstallHandler.
 *
 */

class WikkaInstaller {
    
    # Constants
    const CONFIG_PATH = 'wikka.config.php';
    const MYSQL_ENGINE = 'MyISAM';
    
    /*
     * Properties
     */
    public $report = array();
    public $errors = array();
    private $config = array();
    private $pdo = null;
    
    private $schema_path = '';
    private $default_pages_path = '';
    private $default_page_source_dir = '';
    
    /*
     * Constructor
     */
    public function __construct($config_settings) {
        $this->config = $config_settings;        
        $this->pdo = $this->connect_to_db();
        $this->logs = array();
        $this->report = array();
        
        $this->schema_path = sprintf('install%sschema.php', DIRECTORY_SEPARATOR);
        $this->default_pages_path = sprintf('install%sdefault_pages.php',
            DIRECTORY_SEPARATOR);
        $this->default_page_source_dir = $this->find_lang_default_pages_path();
    }
    
    /*
     * Public Methods
     */
    public function install_wiki() {
        $this->errors = array();
        $this->setup_database();
        $this->create_default_pages();
        $this->build_links_table();
        $this->set_default_acls();
        $this->setup_admin_user();
    }
    
    /*
     * Protected Methods
     */
    protected function connect_to_db() {
        #
        # To simplify testing, require db connection be made explicitly
        # rather than in constructor. This gives test a chance to swap out
        # config settings.
        #
        # Assigns PDO object to $this->pdo and returns it.
        #
        $host = $this->config['mysql_host'];
        $name = $this->config['mysql_database'];
        $user = $this->config['mysql_user'];
        $pass = $this->config['mysql_password'];
        $dsn = sprintf('mysql:host=%s;dbname=%s', $host, $name);
        
        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->pdo;
    }
    
    /*
     * Install Steps
     */
    private function setup_database() {
        $this->report_section_header('Setting Up Database');
        
        # Sets $WikkaDatabaseSchema
        require($this->schema_path);
        
        foreach ($WikkaDatabaseSchema as $key => $sql) {
            $message = sprintf('Create database: %s', $key);
            
            try {                
                $rows_affected = $this->exec_sql($sql);
                $this->report_event(TRUE, $message);
            }
            catch (Exception $e) {
                $this->errors[] = $e;
                $this->report_event(FALSE, $message, $e->getMessage());
            }
        }
        
        return $this;
    }
    
    private function create_default_pages() {
        $this->report_section_header('Creating Default Pages');
        
        # Sets $WikkaInstallDefaultPages
        require($this->default_pages_path);
        
        foreach ($WikkaInstallDefaultPages as $page) {
            $message_f = 'Creating default page: %s';
            
            if ($page == '_rootpage') {
                $page = $this->config['root_page'];
                $fname = 'HomePage';
            }
            else {
                $fname = $page;
            }

            $message = sprintf($message_f, $page);
            
            try {                
                $this->update_default_page($page, $fname);                
                $this->report_event(TRUE, $message);
            }
            catch (Exception $e) {
                $this->errors[] = $e;
                $this->report_event(FALSE, $message, $e->getMessage());
            }
        }
        
        return $this;
    }
    
    private function build_links_table() {
        # This method reimplements this script:
        # https://github.com/wikkawik/WikkaWiki/blob/73c8e/setup/links.php
    }
    
    private function set_default_acls() {
        $this->report_section_header('Setting ACLs for Default Pages');
        
        $default_acls = array(
            # tag => array(read, write, comment_read, comment_post)
            'UserSettings' => array('*', '+', '*', '+'),
            'AdminUsers' => array('!*', '!*', '!*', '!*'),
            'AdminPages' => array('!*', '!*', '!*', '!*'),
            'SysInfo' => array('!*', '!*', '!*', '!*'),
            'WikkaConfig' => array('!*', '!*', '!*', '!*'),
            'DatabaseInfo' => array('!*', '!*', '!*', '!*'),
            'WikkaMenulets' => array('!*', '!*', '!*', '!*'),
            'AdminBadWords' => array('!*', '!*', '!*', '!*'),
            'AdminSpamLog' => array('!*', '!*', '!*', '!*')
        );
        
        $query_f = "INSERT INTO %sacls SET page_tag='%s', read_acl='%s', " .
            "write_acl='%s', comment_read_acl='%s', comment_post_acl='%s'";
        
        foreach ($default_acls as $tag => $acls) {
            list($read_acl, $write_act, $comment_read_acl, $comment_write_acl) = $acls;
            $sql = sprintf($query_f, $this->config['table_prefix'], $tag,
                $read_acl, $write_act, $comment_read_acl, $comment_write_acl);
                
            $message = sprintf('Update ACLs for page: %s', $tag);
            
            try {                
                $rows_affected = $this->exec_sql($sql);
                $this->report_event(TRUE, $message);
            }
            catch (Exception $e) {
                $this->errors[] = $e;
                $this->report_event(FALSE, $message, $e->getMessage());
            }
        }
        
        return $this;
    }
    
    private function setup_admin_user() {
        $this->report_section_header('Set Up Admin User');
        
        # Save admin user (delete first to avoid SQL errors)
        # TODO(klenwell): shouldn't name account for csv values?
        $this->report_event(NULL, 'Insert admin user into database');
        $admin_user = $this->config['admin_users'];
        $admin_email = $this->config['admin_email'];
        $admin_challenge = dechex(crc32(time()));
        
        # Set password
        # TODO(klenwell): Escaping here is unnecessary since we're hashing it
        # (and now using parameterized queries) but changing this now could
        # create problem when authenticating elsewhere (e.g. login).
        $raw_pass = $_SESSION['install']['config']['password'];
        $admin_pass = md5($admin_challenge . mysql_real_escape_string($raw_pass));
        
        # Delete user (if exists)
        $delete_sql_f = 'DELETE FROM %susers WHERE NAME=?';
        $delete_sql = sprintf($delete_sql_f, $this->config['table_prefix']);
        $delete_params = array($admin_user);
        $delete_query = $this->pdo->prepare($delete_sql);
        $delete_query->execute($delete_params);
        
        # Insert admin as user
        $insert_sql_f = 'INSERT INTO %susers SET NAME=?, password=?, email=?, ' .
            'signuptime=NOW(), challenge=?';
        $insert_sql = sprintf($insert_sql_f, $this->config['table_prefix']);
        $insert_params = array($admin_user, $admin_pass, $admin_email,
            $admin_challenge);
        $insert_query = $this->pdo->prepare($insert_sql);
        $insert_query->execute($insert_params);
        
        $message_f = 'Admin user %s saved to database';
        $this->report_event(TRUE, sprintf($message_f, $admin_user));
        
        # Set cookies to login admin user
        $expiration = time() + PERSISTENT_COOKIE_EXPIRY;
        $this->report_event(NULL, 'Setting cookies to auto-login admin');
        SetCookie('user_name@wikka', $admin_user, $expiration, WIKKA_COOKIE_PATH); 
        $_COOKIE['user_name'] = $admin_user; 
        SetCookie('pass@wikka', $admin_pass, $expiration, WIKKA_COOKIE_PATH); 
        $_COOKIE['pass'] = $admin_pass;
        $this->report_event(TRUE, 'Cookies set for admin');
    }
    
    /*
     * Private Methods
     */
    private function find_lang_default_pages_path() {
        $path_f = 'lang%s%s%sdefaults%s';
        $default_path = sprintf($path_f,
            DIRECTORY_SEPARATOR,
            'en',
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $configured_path = sprintf($path_f,
            DIRECTORY_SEPARATOR,
            $this->config['default_lang'],
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        
        if ( file_exists($configured_path) ) {
            return $configured_path;
        }
        else {
            return $default_path;
        }
    }
    
    private function exec_sql($sql) {
        # Replace placeholders
        $sql = str_replace('{{prefix}}', $this->config['table_prefix'], $sql);
        $sql = str_replace('{{engine}}', self::MYSQL_ENGINE, $sql);
        $sql = str_replace('{{db_name}}', $this->config['mysql_database'], $sql);
        
        $rows_affected = $this->pdo->exec($sql);
        return $rows_affected;
    }
    
    private function update_default_page($tag, $fname) {
        $admin_users = explode(',', $this->config['admin_users']);
        $admin_main_user = trim($admin_users[0]);
        
        $path = sprintf('%s%s.php', $this->default_page_source_dir, $fname);
        
        if ( ! file_exists($path) ) {
            throw new Exception('path %s not found', $path);
        }
        elseif ( ! is_readable($path) ) {
            throw new Exception('path %s not readable', $path);
        }
        else {
            # TODO(klenwell): refactor the mechanism for defining default
            # page content. Currently, it is to assign the content as a
            # heredoc to a variable and then echo the variable. Just set the
            # variable, require the path, and insert the content. No need for
            # buffering here.
            ob_start();
            require($path);
            $body = ob_get_contents();
            ob_end_clean();
            
            $note_f = 'Default page installed %s from path %s';
            $page_note = sprintf($note_f, date('Y-m-d H:i:s'), $path);
        }
        
        # Update database
        $update_sql_f = 'UPDATE %spages SET latest="N" WHERE tag=?';
        $update_sql = sprintf($update_sql_f, $this->config['table_prefix']);
        $update_params = array($tag);
        $update_query = $this->pdo->prepare($update_sql);
        $update_query->execute($update_params);
        
        $insert_sql_f = 'INSERT INTO %spages SET tag=?, body=?, ' .
            'user="WikkaInstaller", owner=?, time=NOW(), latest="Y", ' .
            'note=?';
        $insert_sql = sprintf($insert_sql_f, $this->config['table_prefix']);
        $insert_params = array($tag, $body, $admin_main_user, $page_note);
        $insert_query = $this->pdo->prepare($insert_sql);
        $insert_query->execute($insert_params);
        
        return $body;
    }
    
    private function report_section_header($message) {
        $row_f = <<<XHTML
    <div class="row">
      <div class="col-md-4"><h4 class="section">%s</h4></div>
    </div>
XHTML;
        $this->report[] = sprintf($row_f, $message);
    }
    
    private function report_event($success, $message, $detail='') {
        $row_f = <<<XHTML
    <div class="row">
      <div class="col-md-4">%s %s</div>
      <div class="col-md-4">%s</div>
    </div>
XHTML;

        # Prepare result
        $icon_f = '<span class="glyphicon glyphicon-%s" style="color: %s;"></span>';
        $detail_f = '<span class="label label-%s">%s</span>';
        
        if ( is_null($success) ) {
            $icon = sprintf($icon_f, 'info-sign', 'blue');
            $detail_class = 'info';
        }
        elseif ( $success ) {
            $icon = sprintf($icon_f, 'ok', 'green');
            $detail_class = 'success';
        }
        else {
            $icon = sprintf($icon_f, 'flag', 'red');
            $detail_class = 'danger';
        }
        
        if ( $detail ) {
            $detail = sprintf($detail_f, $detail_class, $detail);
        }
        
        $row = sprintf($row_f,
            $icon,
            $message,
            $detail
        );
    
        $this->report[] = $row;
    }
}