<?php

require_once('version.php');
require_once('wikka/constants.php');
require_once('wikka/registry.php');


class WikkaFixture {
    
    /*
     * API
     */
    static public function init() {
        self::init_config();
        self::init_server_env();
    }
    
    static public function init_config() {
        include('wikka/default.config.php');
        include('test/test.config.php');
        $config = array_merge($wakkaDefaultConfig, $wakkaConfig);
        WikkaRegistry::init($config);
    }
    
    static public function init_server_env() {
        $_SERVER = array(
            'SERVER_NAME'   => 'localhost',
            'SERVER_PORT'   => '80',
            'QUERY_STRING'  => 'wakka=HomePage',
            'REQUEST_URI'   => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME'   => '/WikkaWiki/wikka.php',
            'PHP_SELF'      => '/WikkaWiki/wikka.php',
            'REMOTE_ADDR'   => '127.0.0.1'
        );
        return $_SERVER;
    }
    
    static public function tear_down() {
        $_SERVER = array();
        self::tear_down_session();
    }
    
    /*
     * Private Methods
     */
    static private function tear_down_session() {
        if ( session_id() ) {
            session_destroy();
            $_SESSION = array();
        }
    }
}