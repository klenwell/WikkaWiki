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
    public $functional_migrations = '';

    /*
     * Constructor
     */
    public function __construct($migrations_file) {
        require($migrations_file);
        $this->database_migrations = $WikkaDatabaseMigrations;
        $this->functional_migrations = $WikkaFunctionalMigrations;
        
        # Could pass it in but simpler to just load again
        $this->config = $this->load_config();
    }
    
    /*
     * Public Methods
     */
    public function run_migrations($old_version, $new_version) {
        $apply = FALSE;
        
        foreach ( $this->database_migrations as $v => $statements ) {
            #var_dump(array($v, $old_version, $v == $old_version));
            if ( $apply ) {
                # SQL Migrations
                foreach ( $statements as $sql ) {
                    $result = $this->exec_db_migration($sql);
                    $this->log_sql_migration($result);
                }
               
                # Config Migrations
                if ( isset($this->functional_migrations[$v]) ) {
                    foreach ( $this->functional_migrations[$v] as $migration ) {
                        $result = $this->exec_func_migration($migration);
                        $this->log_func_migration($result);
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
    
    private function exec_db_migration($sql) {
        # replace placeholders
        $sql = str_replace('{{prefix}}', $this->config['table_prefix'], $sql);
        $sql = str_replace('{{engine}}', self::MYSQL_ENGINE, $sql);
        
        $result = array(
            'sql' => $sql,
        );
        return $result;
    }
    
    private function exec_func_migration($migration) {
    }
    
    private function log_sql_migration($result) {
        $timestamp = microtime();
        $this->logs[] = $result['sql'];
    }
    
    private function log_func_migration($result) {
    }
}
