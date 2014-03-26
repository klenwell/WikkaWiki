<?php
/**
 * wikka/errors.php
 *
 * Wikka error classes.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 * http://php.net/manual/en/language.exceptions.extending.php
 *
 */
#
# Errors
#
class WikkaError extends Exception {
    public function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return sprintf("%s: %s\n", __CLASS__, $this->message);
    }
}

class WikkaWebServiceError extends WikkaError {}

class WikkaCsrfError extends WikkaError {}

class WikkaHandlerError extends WikkaError {}

class WikkaInstallerError extends WikkaError {
    public function render_problem() { /* Interface Method */ }
    public function render_solution() { /* Interface Method */ }
}


#
# Exceptions and Flags
#
class WikkaInstallInterrupt extends Exception {}
