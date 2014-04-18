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
    public function testCountRevisions() {
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertEquals(3, $page->count_revisions());
    }

    public function testExists() {
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertTrue($page->exists());

        $page = PageModel::find_by_tag('PageThatDoesNotExist');
        $this->assertFalse($page->exists());
    }

    public function testIsLatestVersion() {
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertTrue($page->is_latest_version());

        $page = PageModel::find_by_tag('PageThatDoesNotExist');
        $this->assertFalse($page->is_latest_version());
    }

    public function testIsOwnedBy() {
        $page = PageModel::find_by_tag('WikkaPage');

        $owner = new stdClass;
        $owner->fields = array('name' => 'WikkaOwner');
        $this->assertTrue($page->is_owned_by($owner));

        $user = new stdClass;
        $user->fields = array('name' => 'WikkaUser');
        $this->assertFalse($page->is_owned_by($user));
    }

    public function testLoadAcls() {
        # Create ACLs for WikkaPage

        # Verify WikkaPage ACLs
    }

    public function testPrettyPageTag() {
        $page = PageModel::find_by_tag('WikkaPage');
        $page->fields['tag'] = "A_Page_Tag_With_Underscores";
        $this->assertEquals('A Page Tag With Underscores', $page->pretty_page_tag());
    }

    public function testTagIsValid() {
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertTrue($page->tag_is_valid());

        $page->fields['tag'] = "%I'm bad?%";
        $this->assertFalse($page->tag_is_valid());
    }

    public function testFindByTagAndTime() {
        # How to test this?
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
