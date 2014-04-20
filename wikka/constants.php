<?php
/**
 * wikka/constants.php
 * 
 * Module of main wikka.php script
 *
 * This should be the first module loaded.
 */
#
# Minimum Requirements
#
define('WIKKA_ERROR_LEVEL', E_ALL ^ E_DEPRECATED);
define('MINIMUM_PHP_VERSION', '5.0');
define('MINIMUM_MYSQL_VERSION', '4.1');
define('ERROR_WRONG_PHP_VERSION', 'Wikka requires PHP %s or higher!');

#
# Database
#
define('WIKKA_MYSQL_ENGINE', 'MyISAM');
 
#
# Config
#
define('WIKKA_CONFIG_PATH', 'wikka.config.php');
define('WIKKA_DEFAULT_CONFIG_PATH', 'wikka/default.config.php');
define('WIKKA_MULTI_CONFIG_PATH', 'multi.config.php');
define('WIKKA_CONFIG_VAR', '$wakkaConfig');
define('WIKKA_MIGRATIONS_FILE_PATH', 'install/migrations.php');

#
# Paths
#
define('WIKKA_LIBRARY_PATH', 'lib');
define('PATH_DIVIDER', ',');

#
# URLs
#
define('WIKKA_DOCS_URL', 'http://docs.wikkawiki.org/WikkaInstallation');
define('WIKKA_INSTALL_DOCS_URL', 'http://docs.wikkawiki.org/WikkaInstallation');
define('WIKKA_CONFIG_DOCS_URL', 'http://docs.wikkawiki.org/ConfigurationOptions');
define('WIKKA_GITHUB_URL', 'https://github.com/wikkawik/WikkaWiki');

#
# Cookies
#
define('BASIC_COOKIE_NAME', 'Wikkawiki');
define('DEFAULT_COOKIE_EXPIRATION_HOURS', 90 * 24);
define('PERSISTENT_COOKIE_EXPIRY', 7776000);

#
# Regex Patterns
#   BRACKET_VAR_REGEX: Regex for matching {{ foo }} and {{foo}} used both in
#       templates and model table schema
#   RE_INVALID_WIKI_NAME: Regex for identifying WIKKA_INVALID_CHARS (see below)
#   PATTERN_INVALID_ID_CHARS: Defines characters that are not valid for an ID.
#   PATTERN_REPLACE_IMG_WITH_ALTTEXT: To be used in replacing img tags having
#       an alt attribute with the value of the alt attribute, trimmed.
#       - $result[0]: the entire img tag
#       - $result[1]: If the alt attribute exists, this holds the single
#           character used to delimit the alt string.
#       - $result[2]: The content of the alt attribute, after it has been
#           trimmed, if the attribute exists.
#
define('BRACKET_VAR_REGEX', '/\{\{\s*[^\}]+\}\}/');
define('RE_INVALID_WIKI_NAME', "/[\[\]\{\}%\+\|\?=<>\'\"\/\\x00-\\x1f\\x7f,]/");
define('PATTERN_INVALID_ID_CHARS', '/[^A-Za-z0-9_:.-\s]/');
define('PATTERN_REPLACE_IMG_WITH_ALTTEXT',
    '/<img[^>]*(?<=\\s)alt=("|\')\s*(.*?)\s*\\1.*?>/');
define('RE_VALID_FORMATTER_NAME', '/^([a-zA-Z0-9_.-]+)$/');
define('RE_FULLY_QUALIFIED_URL',
    '/^(http|https|ftp|news|irc|gopher):\/\/([^\\s\"<>]+)$/');
define('RE_EMAIL_ADDRESS', '/^.+\@.+$/');

#
# Miscellaneous
#
define('WIKKA_URL_EXTENSION', 'wikka.php?wakka=');
define('WIKKA_TIMER_START', (float) strtok(microtime(), ' ') + strtok(''));
define('ID_LENGTH', 10);
define('MAX_HOSTNAME_LENGTH_DISPLAY', 50);
define('SPAMLOG_SIG','-@-');
define('WIKKA_INVALID_CHARS', '[ ] { } % + | ? = &lt; &gt; \' " / &amp;');

#
# Long Error Messages
#
$ERROR_MYSQL_SUPPORT_MISSING = <<<HEREDOC
PHP can't find MySQL support but Wikka requires MySQL. Please check the output
of <tt>phpinfo()</tt> in a php document for MySQL support: it needs to be
compiled into PHP, the module itself needs to be present in the expected
location, <strong>and</strong> php.ini needs to have it enabled.<br />Also note
that you cannot have <tt>mysqli</tt> and <tt>mysql</tt> support both enabled at
the same time.<br />Please double-check all of these things, restart your
webserver after any fixes, and then try again!
HEREDOC;
define('ERROR_MYSQL_SUPPORT_MISSING', $ERROR_MYSQL_SUPPORT_MISSING);

$ERROR_WAKKA_LIBRARY_MISSING = <<<HEREDOC
The necessary file "libs/Wakka.class.php" could not be found. To run Wikka,
please make sure the file exists and is placed in the right directory!
HEREDOC;
define('ERROR_WAKKA_LIBRARY_MISSING', $ERROR_WAKKA_LIBRARY_MISSING);
