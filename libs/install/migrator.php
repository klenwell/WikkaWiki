<?php
/**
 * libs/install/migrator.php
 *
 * WikkaMigrator runs migrations during the install process.
 *
 */
require_once('libs/install/installer.php');
 

class WikkaMigrator extends WikkaInstaller {
    
    /*
     * Properties
     */
    public $logs = array();
    public $database_migrations = '';
    public $command_migrations = '';
    
    /*
     * Constructor
     */
    public function __construct($migrations_file) {
        require($migrations_file);
        $this->database_migrations = $WikkaDatabaseMigrations;
        $this->command_migrations = $WikkaCommandMigrations;
        
        # Could pass it in but simpler to just load again. This will also
        # ensure only user config values are loaded (not Wikka defaults).
        $config_settings = $this->load_config();
        
        # These values need to be set if not in config
        $config_settings['default_lang'] = (isset($config_settings['default_lang'])) ?
            $config_settings['default_lang']: 'en';
        $config_settings['table_prefix'] = (isset($config_settings['table_prefix'])) ?
            $config_settings['table_prefix']: '';
        
        parent::__construct($config_settings);
    }
    
    /*
     * Public Methods
     */    
    public function run_migrations($old_version, $new_version) {
        $apply = FALSE;
        
        foreach ( $this->database_migrations as $v => $statements ) {
            
            if ( $apply ) {
                $this->report_section_header(
                    sprintf('Running migrations for version %s', $v));
                
                # SQL Migrations
                foreach ( $statements as $sql ) {
                    $this->run_db_migration($sql);
                }
               
                # Command Migrations
                if ( isset($this->command_migrations[$v]) ) {
                    foreach ( $this->command_migrations[$v] as $command ) {
                        $this->run_command_migration($command);
                    }            
                }
            }
           
            # Found old version, start applying migrations with next migration
            if ( $v == $old_version ) {
                $apply = TRUE;
            }
           
            # Found current version, stop applying migrations
            if ( $v == $new_version ) {
                break;
            }
        }
    }
    
    /*
     * Command Migration Methods
     */
    public function add_config($key, $value) {
        $this->config[$key] = $value;
        return '';
    }
    
    public function delete_path($path) {
        if ( ! file_exists($path) ) {
            return "path not found";
        }
        elseif ( is_file($path) ) {
            unlink($path);
            return sprintf("removed file %s", $path);
        }
        else {
            $this->remove_dir($path);
            return sprintf("removed directory %s", $path);
        }
    }
    
    public function delete_cookie($name) {
        #
        # http://stackoverflow.com/a/14001301/1093087
        #
        setcookie($name, "", time()-3600, '/');
        $_COOKIE[$name] = "";
        return '';
    }
    
    public function update_page($tag) {
        $this->update_default_page($tag);
    }
    
    public function remove_dir($parent_dir) {
        #
        # http://stackoverflow.com/a/15111679/1093087
        # Returns array of paths removed
        #
        $paths = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($parent_dir,
                FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ( $paths as $path ) { 
            if ( $path->isFile() ) {
                unlink($path->getPathname());
            }
            else {
                rmdir($path->getPathname());
            }
        }

        # Don't forget parent dir
        rmdir($parent_dir);
        
        return array_merge(iterator_to_array($paths), array($parent_dir));
    }
    
    public function backup_file($path) {
        if ( ! file_exists($path) ) {
            return "path not found";
        }
        else {
            $dest = sprintf('%s.prev', $path);
            copy($path, $dest);
        }
    }
    
    public function add_menu_config_files() {
        #
        # Adds menu config files and removes config settings navigation_links and
        # logged_in_navigation_links. This is irrelevant to newer version
        # and preserved mainly for historical fidelity.
        #
        # See ticket (both are same):
        # - http://wush.net/trac/wikka/ticket/891
        # - https://github.com/wikkawik/WikkaWiki/issues/885
        #
        $config_path = 'config' . DIRECTORY_SEPARATOR;
        $link_regex = '[A-ZÄÖÜ]+[a-zßäöü]+[A-Z0-9ÄÖÜ][A-Za-z0-9ÄÖÜßäöü]*|\[\[.*?\]\]';
        
        if ( isset($this->config['navigation_links']) ) {
            $links = array();
            $links_found = preg_match_all(sprintf('/%s/', $link_regex),
                $this->config['navigation_links'],
                $links);
            
            if ( $links_found !== FALSE ) {
                if( file_exists($config_path.'main_menu.inc') ) {
                    rename($config_path.'main_menu.inc',
                        $config_path.'main_menu.orig.inc');
                }

                $f = fopen($config_path.'main_menu.inc', 'w');                 
                foreach( $links[0] as $link ) {
                    fwrite($f, $link."\n");
                }
                fwrite($f, "{{searchform}}\nYour hostname is {{whoami}}");
                fclose($f);
            }
            
            unset($this->config['navigation_links']);
        }
        
        if ( isset($this->config['logged_in_navigation_links']) ) {
            $links = array();
            $links_found = preg_match_all(sprintf('/%s/', $link_regex),
                $this->config['logged_in_navigation_links'],
                $links);
            
            if ( $links_found !== FALSE ) {
                if( file_exists($config_path.'main_menu.user.inc') ) {
                    rename($config_path.'main_menu.user.inc',
                        $config_path.'main_menu.user.orig.inc');
                }

                $f = fopen($config_path.'main_menu.user.inc', 'w');                 
                foreach( $links[0] as $link ) {
                    fwrite($f, $link."\n");
                }
                fwrite($f, "{{searchform}}\nYou are {{whoami}}");
                fclose($f);
            }
            
            unset($this->config['logged_in_navigation_links']);
        }
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
        return $this->pdo;
    }
    
    /*
     * Private Methods
     */
    private function load_config() {
        include(self::CONFIG_PATH);
        return $wakkaConfig;
    }
    
    private function run_db_migration($sql) {
        # Replace placeholders
        $sql = str_replace('{{prefix}}', $this->config['table_prefix'], $sql);
        $sql = str_replace('{{engine}}', self::MYSQL_ENGINE, $sql);
        $sql = str_replace('{{db_name}}', $this->config['mysql_database'], $sql);
        
        # Run command
        $rows_affected = $this->pdo->exec($sql);
        
        # Log result
        $this->log_sql_migration($sql, $rows_affected);
        
        return $rows_affected;
    }

    private function run_command_migration($command) {
        list($method, $args) = $command;
        $result = call_user_func_array(array($this, $method), $args);
        $this->log_command_migration($method, $args, $result);
    }
    
    private function log($message) {
        # Less than 5 decimal places was causing key collisions in testing
        $utime = sprintf('%.8f', array_sum(explode(' ', microtime())));
        $this->logs[$utime] = $message;
        return $this->logs;
    }
    
    private function log_sql_migration($sql, $rows_updated) {
        $message = sprintf('%s >> %d rows', $sql, $rows_updated);
        $this->report_event(TRUE,
            sprintf('Migration: updated %s rows affected', $rows_updated),
            $sql
        );
        return $this->log($message);
    }
    
    private function log_command_migration($method, $args, $result='') {
        $tail = ($result) ? sprintf(' >> %s', $result) : '';
        $message = sprintf('%s(%s)%s', $method, implode(', ', $args), $tail);
        $this->report_event(TRUE, sprintf('Update: %s', $message));
        return $this->log($message);
    }
}
