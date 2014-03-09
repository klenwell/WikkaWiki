<?php
/**
 * wikka-refactored.php
 *
 * This is a transitional script which should eventually replace wikka.php.
 *
 * This is the main Wikka script. This file is called each time a request is
 * made from the browser.
 *
 * This file was originally written by Hendrik Mans for WakkaWiki
 * and released under the terms of the modified BSD license
 * @see /docs/WakkaWiki.LICENSE
 *
 * @package Wikka
 * @subpackage Core
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @see /docs/Wikka.LICENSE
 * @filesource
 *
 * @author    {@link http://www.mornography.de/ Hendrik Mans}
 * @author    {@link http://wikkawiki.org/JsnX Jason Tourtelotte}
 * @author    {@link http://wikkawiki.org/JavaWoman Marjolein Katsma}
 * @author    {@link http://wikkawiki.org/NilsLindenberg Nils Lindenberg}
 * @author    {@link http://wikkawiki.org/DotMG Mahefa Randimbisoa}
 * @author    {@link http://wikkawiki.org/DarTar Dario Taraborelli}
 * @author    {@link http://wikkawiki.org/BrianKoontz Brian Koontz}
 * @author    {@link http://wikkawiki.org/TormodHaugen Tormod Haugen}
 * @author    {@link https://github.com/klenwell Tom Atwell}
 *
 * @copyright Copyright 2002-2003, Hendrik Mans <hendrik@mans.de>
 * @copyright Copyright 2004-2005, Jason Tourtelotte <wikka-admin@jsnx.com>
 * @copyright Copyright 2006-2014, {@link http://wikkawiki.org/CreditsPage Wikka Development Team}
 *  
 */
#
# Imports
#
require_once('libs/Compatibility.lib.php');
require_once('libs/Wakka.class.php');
require_once('wikka/helpers.php');
require_once('wikka/constants.php');

#
# Main Script
#
$webservice = new WikkaWebService();
$response = $webservice->process_request();
$reponse->send_headers();
print($response->body);
