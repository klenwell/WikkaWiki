<?php
/**
 * libs/install/migrator.php
 *
 * WikkaMigrator runs migrations during the install process.
 *
 */

class WikkaMigrator {
    
    const CONFIG_PATH = 'wikka.config.php';
    const MYSQL_ENGINE = 'MyISAM';
    
    /*
     * Properties
     */
    public $logs = array();
    public $database_migrations = '';
    public $command_migrations = '';

    private $pdo = null;
    
    /*
     * Constructor
     */
    public function __construct($migrations_file) {
        require($migrations_file);
        $this->database_migrations = $WikkaDatabaseMigrations;
        $this->command_migrations = $WikkaCommandMigrations;
        
        # Could pass it in but simpler to just load again
        $this->config = $this->load_config();
    }
    
    /*
     * Public Methods
     */
    public function connect_to_db() {
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
    
    public function run_migrations($old_version, $new_version) {
        $apply = FALSE;
        
        foreach ( $this->database_migrations as $v => $statements ) {
            #var_dump(array($v, $old_version, $v == $old_version));
            if ( $apply ) {
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
     * Private Methods
     */
    private function load_config() {
        include(self::CONFIG_PATH);
        $this->config = $wakkaConfig;
    }
    
    private function run_db_migration($sql) {
        # Replace placeholders
        $sql = str_replace('{{prefix}}', $this->config['table_prefix'], $sql);
        $sql = str_replace('{{engine}}', self::MYSQL_ENGINE, $sql);
        
        # Run command
        $rows_affected = $this->pdo->exec($sql);
        
        # Log result
        $this->log_sql_migration($sql, $rows_affected);
        
        return $rows_affected;
    }

    private function run_command_migration($migration) {
    }
    
    private function log($message) {
        $utime = sprintf('%.4f', array_sum(explode(' ', microtime())));
        $this->logs[$utime] = $message;
        return $this->logs;
    }
    
    private function log_sql_migration($sql, $rows_updated) {
        $MAX_LEN = 60;
        $sql_log = (strlen($sql) > $MAX_LEN) ? substr($sql,0,$MAX_LEN)."..." : $sql;
        $message = sprintf('%s >> %d rows', $sql, $rows_updated);
        return $this->log($message);
    }
    
    private function log_func_migration($result) {
    }
}
