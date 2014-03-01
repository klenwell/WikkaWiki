<?php
/**
 * Refactored Wikka Show Handler
 *
 * Display a page if the user has read access or is an admin.
 *
 * This is the default page handler used by Wikka when no other handler is specified.
 * It is used by the Wakka class to produce HTML output.
 * 
 * Depending on user privileges, it returns the page body or an error message. It also
 * includes footer comments and a form to post comments, depending on ACL and general 
 * config settings.
 *
 * USAGE
 *
 * NOTES
 * A refactor of the Wikka show handler to function as a more independent
 * modular unit that can be more effectively tested.
 *
 * @package		Handlers
 * @license		http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014       Tom Atwell <klenwell@gmail.com>
 *
 */

class ShowHandler {
}