<?php
/**
 * wikka/PageModelTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/models/PageModelTest
 *
 */
require_once('models/page.php');
require_once('test/fixtures/wikka.php');
require_once('test/fixtures/models.php');
require_once('wikka/registry.php');


class PageModelTest extends PHPUnit_Framework_TestCase {

    /**
     * Test Fixtures
     */
    public function setUp() {
        WikkaFixture::init();
        $this->model = PageModelFixture::init();
    }

    public function tearDown() {
        WikkaFixture::tear_down();
        PageModelFixture::tear_down();
    }

    /**
     * Tests
     */
    public function testExists() {
    }

    public function testIsLatestVersion() {
    }

    public function testIsOwnedBy() {
    }

    public function testLoadAcls() {
    }

    public function testPrettyPageTag() {
    }

    public function testTagIsValid() {
    }

    public function testFindByTagAndTime() {
    }

    public function testFindByTag() {
    }

    public function testSave() {
    }

    public function testInstantiates() {
        $this->assertInstanceOf('PageModel', $this->model);
    }
}
