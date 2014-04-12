<?php
/**
 * models/link.php
 *
 * Link model class.
 *
 * @package		Models
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('models/base.php');

 

class LinkModel extends WikkaModel {
    /*
     * Static Properties
     */
    protected static $schema = <<<MYSQL
CREATE TABLE {{prefix}}links (
	from_tag varchar(75) NOT NULL default '',
	to_tag varchar(75) NOT NULL default '',
	UNIQUE KEY from_tag (from_tag, to_tag),
	KEY idx_to (to_tag)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE={{engine}}
MYSQL;
    
    protected static $table = 'links';
    
}
