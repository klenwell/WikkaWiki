<?php
/**
 * wikka/UserModelTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/models/UserModelTest
 *
 */
require_once('models/user.php');
require_once('test/fixtures/wikka.php');
require_once('test/fixtures/models.php');
require_once('wikka/registry.php');


class UserModelTest extends PHPUnit_Framework_TestCase {

    /**
     * Test Fixtures
     */
    public function setUp() {
        WikkaFixture::init();
        $this->model = UserModelFixture::init();
        PageModelFixture::init();
        AclModelFixture::init();
    }

    public function tearDown() {
        WikkaFixture::tear_down();
    }

    /**
     * Tests
     */
    public function testWantsCommentsForPage() {
        $page = PageModel::find_by_tag('WikkaPage');

        $wikka_user = UserModel::find_by_name('WikkaUser');
        $wants_comments = $wikka_user->wants_comments_for_page($page);
        $this->assertEquals('Y', $wants_comments);

        $wikka_admin = UserModel::find_by_name('WikkaAdmin');
        $wants_comments = $wikka_admin->wants_comments_for_page($page);
        $this->assertEquals('N', $wants_comments);
    }

    public function testBelongsToGroup() {
        $group_page = PageModel::init(array(
            'tag' => 'WikkaGroup',
            'owner' => 'WikkaAdmin',
            'user' => 'WikkaAdmin',
            'title' => 'Wikka Group Page',
            'body' => '+WikkaAdmin++WikkaUser+'
        ));
        $group_page->save();

        $anonymous_user = UserModel::find_by_name('UnregisteredWikkaUser');
        $wikka_user = UserModel::find_by_name('WikkaUser');
        $wikka_admin = UserModel::find_by_name('WikkaAdmin');

        $this->assertFalse($anonymous_user->belongs_to_group('WikkaGroup'));
        $this->assertTrue($wikka_user->belongs_to_group('WikkaGroup'));
        $this->assertTrue($wikka_admin->belongs_to_group('WikkaGroup'));
    }

    public function testCan() {
        # Load page
        $page = PageModel::find_by_tag('WikkaPage');
        $this->assertTrue($page->exists());

        # Anonymous User
        $anonymous_user = UserModel::find_by_name('UnregisteredWikkaUser');
        $this->assertTrue($anonymous_user->can('read', $page));
        $this->assertFalse($anonymous_user->can('write', $page));
        $this->assertFalse($anonymous_user->can('write_comment', $page));

        # Wikka User
        $wikka_user = UserModel::find_by_name('WikkaUser');
        $this->assertTrue($wikka_user->can('read', $page));
        $this->assertTrue($wikka_user->can('write', $page));
        $this->assertFalse($wikka_user->can('write_comment', $page));

        # Wikka Admin
        $wikka_admin = UserModel::find_by_name('WikkaAdmin');
        $this->assertTrue($wikka_admin->can('read', $page));
        $this->assertTrue($wikka_admin->can('write', $page));
        $this->assertTrue($wikka_admin->can('write_comment', $page));
    }

    public function testExists() {
        $wikka_user = UserModel::find_by_name('WikkaUser');
        $this->assertTrue($wikka_user->exists());

        $anonymous_user = UserModel::find_by_name('UnregisteredWikkaUser');
        $this->assertFalse($anonymous_user->exists());
    }

    public function testIsLoggedIn() {
        $wikka_user = UserModel::find_by_name('WikkaUser');
        $this->assertTrue($wikka_user->is_logged_in());

        $anonymous_user = UserModel::find_by_name('UnregisteredWikkaUser');
        $this->assertFalse($anonymous_user->is_logged_in());
    }

    public function testIsAdmin() {
        $wikka_admin = UserModel::find_by_name('WikkaAdmin');
        $this->assertTrue($wikka_admin->is_admin());

        $wikka_user = UserModel::find_by_name('WikkaUser');
        $this->assertFalse($wikka_user->is_admin());
    }

    public function testFindByName() {
        $wikka_user = UserModel::find_by_name('WikkaUser');
        $this->assertTrue($wikka_user->exists());
        $this->assertEquals('WikkaUser', $wikka_user->field('name'));
    }

    public function testLoad() {
        $_SESSION['user'] = array('name' => 'WikkaUser');
        $wikka_user = UserModel::load();
        $this->assertTrue($wikka_user->exists());
        $this->assertEquals('WikkaUser', $wikka_user->field('name'));
    }

    public function testLoadUnregisteredVisitor() {
        $wikka_user = UserModel::load();
        $this->assertFalse($wikka_user->exists());
    }

    public function testInstantiates() {
        $this->assertInstanceOf('UserModel', $this->model);
    }
}
