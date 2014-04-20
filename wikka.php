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
require_once('version.php');            # TODO: merge into constants
require_once('wikka/constants.php');
require_once('wikka/functions.php');    # TODO: eliminate this (replace with libs)
require_once('wikka/errors.php');
require_once('wikka/web_service.php');

# TODO(klenwell): refactor and remove this. The wakka formatter class
# requires this library for the instantiate function.
require_once('libs/Compatibility.lib.php');

#
# Main Script
#
$webservice = new WikkaWebService();
$webservice->disable_magic_quotes_if_enabled();
$webservice->prepare_request();

try {
    $webservice->start_session();
    $webservice->authenticate_if_locked();
    $webservice->enforce_csrf_token();
    $webservice->interrupt_if_install_required();
    $response = $webservice->dispatch();
}
catch (WikkaInstallInterrupt $e) {
    $response = $webservice->dispatch_to_installer();
}
catch (Exception $e) {
    $response = $webservice->process_error($e);
}

$response->send_headers();
$response->render();
