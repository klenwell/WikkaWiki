<?php
/**
 * main/SECTION.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */
if ( version_compare(phpversion(),'5.3','<') ) {
    error_reporting(E_ALL);
}
else {
    error_reporting(E_ALL & !E_DEPRECATED);
}