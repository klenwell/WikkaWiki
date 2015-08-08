<?php
/**
 * handlers/ShowHandlerTest.php
 *
 * Test new ShowHandler class.
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/handlers/InstallHandlerTest
 *
 */
require_once('wikka/constants.php');
require_once('wikka/registry.php');
require_once('wikka/request.php');
require_once('libs/Compatibility.lib.php');
require_once('3rdparty/core/php-gettext/gettext.inc');
require_once('lang/en/en.inc.php');
require_once('libs/Wakka.class.php');
require_once('version.php');
require_once('handlers/install.php');
require_once('models/base.php');
require_once('wikka/web_service.php');


class InstallHandlerTest extends PHPUnit_Framework_TestCase {

    protected static $wakka;
    protected static $config;

    /**
     * Test Fixtures
     */
    public function setUp() {
        self::$config = $this->setUpConfig();
        $this->setUpMockServerEnvironment();
        WikkaRegistry::init(self::$config);

        $request = new WikkaRequest();
        $this->install_handler = new InstallHandler($request);
    }

    public function tearDown() {
        $this->install_handler = NULL;
        $this->wikka = NULL;
    }

    private function setUpConfig() {
        include('wikka/default.config.php');
        return $wakkaDefaultConfig;
    }

    private function setUpMockServerEnvironment() {
        $_SERVER = array(
            'SERVER_NAME'   => 'localhost',
            'SERVER_PORT'   => '80',
            'QUERY_STRING'  => 'wakka=HomePage',
            'REQUEST_URI'   => '/WikkaWiki/wikka.php?wakka=HomePage',
            'SCRIPT_NAME'   => '/WikkaWiki/wikka.php',
            'REMOTE_ADDR'   => '127.0.0.1'
        );

        $_GET = array();
    }

    private function mockWebService() {
        $webservice = new WikkaWebService();
        $webservice->disable_magic_quotes_if_enabled();
        $webservice->prepare_request();
        $webservice->start_session();
        $webservice->authenticate_if_locked();
        $webservice->enforce_csrf_token();
        return $webservice;
    }


    /**
     * Tests
     */
    public function testShouldLoadIntroStage() {
        $this->mockWebService();
        $response = $this->install_handler->handle();
        $this->assertEquals($response->status, 200);
        $this->assertContains('<title>Wikka Installation</title>', $response->body);
        $this->assertContains('<div class="intro">', $response->body);
        $this->assertContains('This installer will try to write some', $response->body);
    }

    public function testHandlerInstantiation() {
        $this->assertInstanceOf('InstallHandler', $this->install_handler);
        $this->assertEquals($this->install_handler->content_type, 'text/html; charset=utf-8');
    }
}
