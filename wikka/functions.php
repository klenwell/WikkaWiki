<?php
/**
 * main/functions.php
 * 
 * Module of main wikka.php script
 *
 * Globally useful functions. This should be loaded at the beginning of the
 * main wikka script.
 */
function define_constant_if_not_defined($name, $value) {
    if ( ! defined($name) ) {
        define($name, $value);
    }
}
