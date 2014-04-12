<?php
/**
 * models/referrer.php
 *
 * WikkaWiki Access Control List model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');

 

class ReferrerModel extends WikkaModel {
    /*
     * Static Properties
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}referrers (
	page_tag varchar(75) NOT NULL default '',
	referrer varchar(255) NOT NULL default '',
	time datetime NOT NULL default '0000-00-00 00:00:00',
	KEY idx_page_tag (page_tag),
	KEY idx_time (time)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    protected static $table = 'referrers';
    
}
