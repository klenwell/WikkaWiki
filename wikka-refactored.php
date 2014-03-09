<?php
/**
 * wikka-refactored.php
 *
 * This is a transitional script which should eventually replace wikka.php.
 *
 * This is the main Wikka script. This file is called each time a request is
 * made from the browser.
 *
 * @author    {@link https://github.com/klenwell Tom Atwell}
 *
 * @copyright Copyright 2014, Tom Atwell <klenwell@gmail.com>
 *  
 */
#
# Imports
#
require_once('wikka/constants.php');
require_once('wikka/web_service.php');

#
# Main Script
#
$webservice = new WikkaWebService();
$response = $webservice->process_request();
$reponse->send_headers();
print($response->body);
