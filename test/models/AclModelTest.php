<?php
/**
 * wikka/AclModelTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/models/AclModelTest
 *
 */
require_once('models/acl.php');
require_once('test/fixtures/wikka.php');
require_once('test/fixtures/models.php');
require_once('wikka/registry.php');



class AclModelTest extends PHPUnit_Framework_TestCase {
 
    /**
     * Test Fixtures
     */
    public function setUp() {
        WikkaFixture::init();
        $this->model = AclModelFixture::init();
    }
    
    public function tearDown() {
        WikkaFixture::tear_down();
        AclModelFixture::tear_down();
    }
    
    /**
     * Tests
     */
    public function testFindByPageTagForUnsavedPage() {
        $page_acl = AccessControlListModel::find_by_page_tag('SecretPageNotFound');
        $this->assertEquals('SecretPageNotFound', $page_acl->field('page_tag'));
        $this->assertEquals(WikkaRegistry::get_config('default_write_acl'),
            $page_acl->field('write_acl'));
    }
    
    public function testFindByPageTag() {
        $page_acl = AccessControlListModel::find_by_page_tag('SecretPage');
        $this->assertEquals('SecretPage', $page_acl->field('page_tag'));
        $this->assertEquals('!NSA', $page_acl->field('read_acl'));
    }
    
    public function testLoadDefaults() {
        $defaults = AccessControlListModel::load_defaults();
        $this->assertEquals('*', $defaults['read_acl']);
        $this->assertEquals('+', $defaults['write_acl']);
        $this->assertEquals('*', $defaults['comment_read_acl']);
        $this->assertEquals('+', $defaults['comment_post_acl']);
    }
    
    public function testSaveRecord() {
        $this->model->fields = array(
            'page_tag' => 'LockedPage',
            'read_acl' => '!*',
            'write_acl' => '!*',
            'comment_read_acl' => '!*',
            'comment_post_acl' => '!*',
        );
        
        $query = $this->model->save();
        $this->assertEquals(1, $query->rowCount());
    }
    
    public function testInstantiates() {
        $this->assertInstanceOf('AccessControlListModel', $this->model);
    }
}
