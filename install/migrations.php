<?php

$WikkaMysqlMigrations = array(

    # version 0.1 to next (0.1.1)
    '0.1' => array(
        "ALTER TABLE {{prefix}}pages ADD body_r TEXT NOT NULL DEFAULT '' AFTER body",
    ),
    
    '0.1.1' => array(),
    '0.1.2' => array(),
    
    '0.1.3-dev' => array(
        "ALTER TABLE {{prefix}}pages ADD note varchar(50) NOT NULL default '' after latest",
        "ALTER TABLE {{prefix}}pages DROP COLUMN body_r",
        "ALTER TABLE {{prefix}}users DROP COLUMN motto"
    ),
    
    '1.0'   => array(),
    '1.0.1' => array(),
    '1.0.2' => array(),
    '1.0.3' => array(),
    '1.0.4' => array(),
    '1.0.5' => array(),
    
    '1.0.6' => array(
        "",
        "",
        "",
        "",
        ""
    ),
);


$WikkaConfigMigrations = array(
    '1.0.4' => array(
        'double_doublequote_html' => array('ADD' => 'safe'),
    ),
);