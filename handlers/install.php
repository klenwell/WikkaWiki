<?php
/**
 * Install Handler
 *
 * Handles all stages of install process:
 *  1. Configuration Form
 *  2. Configuration Summary
 *  3. Write Config File
 *  4. Write Config Summary
 *
 * USAGE
 *
 * NOTES
 * A refactor of the Wikka show handler to function as a more independent
 * modular unit that can be more effectively tested.
 *
 * @package     Handlers
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 */
require_once('handlers/base.php');
require_once('wikka/response.php');


class InstallHandler extends WikkaHandler {
    
    /*
     * Properties
     */
    # For Content-type header
    public $content_type = 'text/html; charset=utf-8';
    
    # Template
    public $template = <<<HTML
<div id="install">
    %s
    <div style="clear: both"></div>
</div>

%s
HTML;

    # Template Vars (%s from template above in order)
    protected $stage_content = '';
    
    /*
     * Main Handler Method
     */
    public function handle() {
    }
    
    /*
     * Format Methods
     */
    protected function format_content() {
        return sprintf($this->template, $this->stage_content);
    }
}
