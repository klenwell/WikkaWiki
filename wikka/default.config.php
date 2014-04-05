<?php
/**
 * main/default.config.php
 * 
 * Default config file used by main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

$wakkaDefaultConfig = array(
    'default_config_loaded'     => true,
    'wakka_version'             => 0,
    
    'mysql_host'                => 'localhost',
    'mysql_database'            => 'wikka',
    'mysql_user'                => 'wikka',
    'table_prefix'              => 'wikka_',

    'root_page'                 => 'HomePage',
    'wakka_name'                => 'MyWikkaSite',
    'rewrite_mode'              => '0',
    'wiki_suffix'               => '@wikka',
    
    # enable (1) or disable (0, default) lookup of user hostname from IP address
    'enable_user_host_lookup'   => '0',    

    'action_path'               => 'plugins/actions,actions',
    'handler_path'              => 'plugins/handlers,handlers',
    'lang_path'                 => 'plugins/lang',
    'gui_editor'                => '1',
    'default_comment_display'   => 'threaded',
    'theme'                     => 'light',

    #
    # formatter and code highlighting paths
    #
    # (location of Wikka formatter - REQUIRED)
    'wikka_formatter_path'      => 'plugins/formatters,formatters',
    
    # (location of Wikka code highlighters - REQUIRED)
    'wikka_highlighters_path'   => 'formatters',
    
    # (location of GeSHi package)
    'geshi_path'                => '3rdparty/plugins/geshi',
    
    # (location of GeSHi language highlighting files)
    'geshi_languages_path'      => '3rdparty/plugins/geshi/geshi',        

    #
    # template
    #
    # (location of Wikka template files - REQUIRED)
    'wikka_template_path'       => 'plugins/templates,templates',        
    'feedcreator_path'          => '3rdparty/core/feedcreator',
    'safehtml_path'             => '3rdparty/core/safehtml',
    'referrers_purge_time'      => '30',
    'pages_purge_time'          => '0',
    'xml_recent_changes'        => '10',
    'hide_comments'             => '0',
    
    # edit note optional (0, default), edit note required (1) edit note disabled (2)
    'require_edit_note'         => '0',        
    'anony_delete_own_comments' => '1',
    
    # enable or disable public display of system information in SysInfo
    'public_sysinfo'            => '0',        
    'double_doublequote_html'   => 'safe',
    'sql_debugging'             => '0',
    'admin_users'               => '',
    'admin_email'               => '',
    'upload_path'               => 'uploads',
    'mime_types'                => 'mime_types.txt',

    #
    # code hilighting with GeSHi
    #
    # 'div' (default) or 'pre' to surround code block
    'geshi_header'                => 'div',
    
    # disable line numbers (0), or enable normal (1) or fancy line numbers (2)
    'geshi_line_numbers'        => '1',        
    'geshi_tab_width'           => '4',        # set tab width
    'grabcode_button'           => '1',        # allow code block downloading

    'wikiping_server'           => '',

    'default_write_acl'         => '+',
    'default_read_acl'          => '*',
    'default_comment_read_acl'  => '*',
    'default_comment_post_acl'  => '+',
    'allow_user_registration'   => '1',
    'enable_version_check'      => '1',
    'version_check_interval'    => '1h',
    'default_lang'              => 'en',
    'spamlog_path'              => './spamlog.txt.php',
    'badwords_path'             => './badwords.txt.php',
    'spam_logging'              => '0',
    'content_filtering'         => '0',
    'max_new_document_urls'     => '15',
    'max_new_comment_urls'      => '6',
    'max_new_feedback_urls'     => '6',
    'utf8_compat_search'        => '0'
);
