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
require_once('version.php');
require_once('wikka/constants.php');
require_once('wikka/functions.php');
require_once('wikka/web_service.php');
require_once('wikka/errors.php');

# TODO(klenwell): refactor and remove this. The wakka formatter class
# requires this library for the instantiate function.
require_once('libs/Compatibility.lib.php');

#
# Main Script
#
$webservice = new WikkaWebService();
$webservice->disable_magic_quotes_if_enabled();

try {
    $request = $webservice->prepare_request();
    $webservice->start_session();
    $webservice->set_csrf_token();
    $response = $webservice->process_request($request);
}
catch (Exception $e) {
    $response = $webservice->process_error($e);
}

$response->send_headers();
$response->render();
