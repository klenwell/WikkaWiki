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
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertEquals('Y', $page->field('latest'));
        $this->assertEquals('version 3', $page->field('note'));
    }

    public function testSave() {
        $this->model->fields = array(
            'tag' => 'WikkaPage',
            'owner' => 'WikkaOwner',
            'user' => 'WikkaUser',
            'title' => 'Wikka Page (Updated)',
            'body' => 'Meet the new boss. Same as the old boss',
            'note' => 'version whatever'
        );

        $query = $this->model->save();
        $this->assertEquals(1, $query->rowCount());
    }

    public function testInstantiates() {
        $this->assertInstanceOf('PageModel', $this->model);
    }
}
