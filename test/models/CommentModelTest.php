<?php
/**
 * wikka/CommentModelTest.php
 *
 * Usage (run from WikkaWiki root dir):
 * > phpunit --stderr test/models/CommentModelTest
 *
 */
require_once('models/comment.php');
require_once('test/fixtures/wikka.php');
require_once('test/fixtures/models.php');
require_once('wikka/registry.php');


class CommentModelTest extends PHPUnit_Framework_TestCase {

    /**
     * Test Fixtures
     */
    public function setUp() {
        WikkaFixture::init();
        $this->model = CommentModelFixture::init();
    }

    public function tearDown() {
        WikkaFixture::tear_down();
    }

    /**
     * Tests
     */
    public function testFindByPageTagInThreadedOrder() {
        $comments = CommentModel::find_by_page_tag_in_threaded_order('CommentBoard');
        $this->assertEquals('Parent Comment #1', $comments[0]['comment']);
        $this->assertEquals('Child #2 of Parent Comment #1',
            $comments[2]['comment']);
        $this->assertEquals('Grandchild #1 of Parent Comment #2',
            $comments[5]['comment']);
    }

    public function testFindByPageTagInAscendingOrder() {
        $comments = CommentModel::find_by_page_tag_as_array('CommentBoard',
            COMMENT_ORDER_DATE_ASC);
        $this->assertEquals('Parent Comment #1', $comments[0]['comment']);
        $this->assertEquals('Grandchild #1 of Parent Comment #2',
            $comments[count($comments)-1]['comment']);
    }

    public function testCountByPageTag() {
        $count = CommentModel::count_by_page_tag('CommentBoard');
        $this->assertEquals(6, $count);
    }

    public function testSaveRecord() {
        $this->model->fields = array(
            'page_tag' => 'WikkaHoneyPot',
            'user' => 'MechanicalTurk1483',
            'comment' => "Eat at Joe's"
        );

        $query = $this->model->save();
        $this->assertEquals(1, $query->rowCount());
    }

    public function testInstantiates() {
        $this->assertInstanceOf('CommentModel', $this->model);
    }
}
