<?php

class WikkaModelFixture {
    static public function init_database() {
        $config = WikkaRegistry::$config;
        
        # Create db connection
        $host = sprintf('mysql:host=%s', $config['mysql_host']);
        $pdo = new PDO($host, $config['mysql_user'], $config['mysql_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        # Create database
        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $config['mysql_database']));
        $pdo->exec(sprintf('CREATE DATABASE `%s`', $config['mysql_database']));
        $pdo->query(sprintf('USE %s', $config['mysql_database']));
        
        return WikkaRegistry::connect_to_db();
    }
    
    static public function init_table($model) {
        $class = get_class($model);
        $model->pdo->exec($class::get_schema());
    }
    
    static public function init_fixture_data($model, $data) {
        foreach ( $data as $record ) {
            $model->fields = $record;
            $model->save();
        }
    }
    
    static public function tear_down() {
        $pdo = WikkaRegistry::connect_to_db();
        $pdo->exec(sprintf('DROP DATABASE `%s`',
            WikkaRegistry::get_config('mysql_database')));
        WikkaRegistry::disconnect_from_db();
    }
}


class AclModelFixture extends WikkaModelFixture {
    
    static public $data = array(
        array(
            'page_tag' => 'SecretPage',
            'read_acl' => '!NSA',
            'write_acl' => '!NSA',
            'comment_read_acl' => '!NSA',
            'comment_post_acl' => '!NSA',
        )
    );

    static public function init() {
        WikkaModelFixture::init_database();
        $model = new AccessControlListModel();
        
        WikkaModelFixture::init_table($model);
        WikkaModelFixture::init_fixture_data($model, self::$data);
        
        return $model;
    }
    
    static public function tear_down() {
        WikkaModelFixture::tear_down();
    }
}