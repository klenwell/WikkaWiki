<?php
/**
 * libs/install/migrator.php
 *
 * WikkaMigrator runs migrations during the install process.
 *
 */

class WikkaMigrator {
    /*
     * Properties
     */
    public $database_migrations = '';
    public $functional_migrations = '';

    /*
     * Constructor
     */
    public function __construct($migrations_file) {
        require($migrations_file);
        $this->database_migrations = $WikkaDatabaseMigrations;
        $this->functional_migrations = $WikkaFunctionalMigrations;
    }
    
    /*
     * Public Methods
     */
    public function run_migrations($old_version, $new_version) {
    }
    
    /*
     * Private Methods
     */
}
