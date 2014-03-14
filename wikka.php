<?php
/**
 * wikka.php
 *
 * This is the main Wikka script. This file is called each time a request is
 * made from the browser.
 *
 * @author    {@link http://www.mornography.de/ Hendrik Mans}
 * @author    {@link http://wikkawiki.org/JsnX Jason Tourtelotte}
 * @author    {@link http://wikkawiki.org/JavaWoman Marjolein Katsma}
 * @author    {@link http://wikkawiki.org/NilsLindenberg Nils Lindenberg}
 * @author    {@link http://wikkawiki.org/DotMG Mahefa Randimbisoa}
 * @author    {@link http://wikkawiki.org/DarTar Dario Taraborelli}
 * @author    {@link http://wikkawiki.org/BrianKoontz Brian Koontz}
 * @author    {@link http://wikkawiki.org/TormodHaugen Tormod Haugen}
 * @author    {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 *
 * @copyright Copyright 2002-2003, Hendrik Mans <hendrik@mans.de>
 * @copyright Copyright 2004-2005, Jason Tourtelotte <wikka-admin@jsnx.com>
 * @copyright Copyright 2006-2014, {@link http://wikkawiki.org/CreditsPage Wikka Development Team}
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
