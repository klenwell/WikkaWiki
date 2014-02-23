<?php
/**
 * main/multisite.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

/**
 * To activate multisite deployment capabilities, just create an empty file
 * multi.config.php in your Wikkawiki installation directory. This file can
 * contain an array definition for $multiConfig.
 * 
 * Relevant keys in the array are a global directory for local settings
 * 'local_config' and designated directories for different host requests, e.g.
 * you may want http://example.com and http://www.example.com using the same
 * local config file.
 *
 * 'http_www_example_com' => 'http.example.com'
 * 'http_example_com' => 'http.example.com'
*/

# Set some config values
$wakkaGlobalConfig = $wakkaConfig;	    # copy config file, #878
$multiDefaultConfig = array(
    'local_config' => 'wikka.config'    # path to local configs
);
$multiConfig = array();

# Include the multi-config file
include('multi.config.php');

# Merge default multi config with config from file
$multiConfig = array_merge($multiDefaultConfig, $multiConfig);    

# t_ values! (See wikka/domain_path.php)
$configkey = str_replace('://','_',$t_scheme).str_replace('.','_',$t_domain);
if( $t_port != '' ) {
    $configkey .= '_'.$t_port;
}


/**
 * Admin can decide to put a specific local config in a more readable and
 * shorter directory. The $configkey is created as
 * 'protocol_thirdleveldomain_secondleveldomain_topleveldomain' Subdirectories
 * are not supported at the moment, but should be easy to implement.
 *
 * If no designated directory is found in multi.config.php, the script uses
 * the $configkey value and replaces all underscore by dots:
 * protocol.thirdleveldomain.secondleveldomain.topleveldomain e.g.
 * http.www.example.com
*/
if ( isset($multiConfig[$configkey]) ) {
    $configpath = $multiConfig[$configkey];
}
else {
    $requested_host = str_replace('_','.',$configkey);
    $configpath = $multiConfig['local_config'].DIRECTORY_SEPARATOR.$requested_host;
    $multiConfig[$configkey] = $requested_host;
}

$local_configfile = $configpath.DIRECTORY_SEPARATOR.'local.config.php';

/**
 * As each site may differ in its configuration and capabilities, we should
 * consider using plugin directories below the $configpath. Effectively, this
 * replaces the 1.1.6.6 plugins folder. It goes even a little bit further by
 * providing a site specific upload directory.
*/
function build_local_config_path($dirname) {
    $path_f = $configpath.DIRECTORY_SEPARATOR.'%s' . PATH_DIVIDER .
        'plugins'.DIRECTORY_SEPARATOR.'%s' . PATH_DIVIDER . '%s';
    return sprintf($path_f, $dirname, $dirname, $dirname);
}

$localDefaultConfig = array(
    'menu_config_path'			=> build_local_config_path('config'),
    'action_path'				=> build_local_config_path('actions'),
    'handler_path'				=> build_local_config_path('handlers'),
    'wikka_formatter_path'		=> build_local_config_path('formatters'),
    'wikka_highlighters_path'	=> build_local_config_path('formatters'),
    'wikka_template_path'		=> build_local_config_path('templates'),
    'upload_path'				=> $configpath.DIRECTORY_SEPARATOR.'uploads'
);

$localConfig = array();
if ( ! file_exists($configpath) )
{
    $path_parts = explode(DIRECTORY_SEPARATOR, $configpath);
    $partialpath = '';
    foreach( $path_parts as $part ) {
        $partialpath .= $part;
        if (!file_exists($partialpath)) mkdir($partialpath,0755);
        $partialpath .= DIRECTORY_SEPARATOR;
    }
    mkdir($configpath.DIRECTORY_SEPARATOR.'config',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'actions',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'handlers',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'handlers'.DIRECTORY_SEPARATOR.'page',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'formatters',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'templates',0700);
    mkdir($configpath.DIRECTORY_SEPARATOR.'uploads',0755);
}
elseif ( file_exists($local_configfile) ) {
    include($local_configfile);
}

# merge global config with default local config
$wakkaGlobalConfig = array_merge($wakkaGlobalConfig, $localDefaultConfig);    

$wakkaConfigLocation = $local_configfile;

# merge localized global config with local config from file
$wakkaConfig = array_merge($wakkaGlobalConfig, $wakkaConfig);    
