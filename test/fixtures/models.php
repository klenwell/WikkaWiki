<?php
require_once('wikka/registry.php');
require_once('models/acl.php');
require_once('models/comment.php');
require_once('models/page.php');
require_once('models/user.php');



class WikkaModelFixture {
    static public function init_database() {
        $config = WikkaRegistry::$config;

        # Create db connection
        $host = sprintf('mysql:host=%s', $config['mysql_host']);
        $pdo = new PDO($host, $config['mysql_user'], $config['mysql_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        # Create database
        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $config['mysql_database']));
        $pdo->exec(sprintf('CREATE DATABASE `%s`', $config['mysql_database']));
        $pdo->query(sprintf('USE %s', $config['mysql_database']));

        return WikkaRegistry::connect_to_db();
    }

    static public function init_table($model) {
        $class = get_class($model);
        $model->pdo->exec($class::get_schema());
    }

    static public function init_fixture_data($model, $data) {
        foreach ( $data as $record ) {
            $model->fields = $record;
            $model->save();
        }
    }

    static public function tear_down() {
        $pdo = WikkaRegistry::connect_to_db();
        $pdo->exec(sprintf('DROP DATABASE `%s`',
            WikkaRegistry::get_config('mysql_database')));
        WikkaRegistry::disconnect_from_db();
    }
}


class UserModelFixture extends WikkaModelFixture {

    static public $data = array(
        array(
            'name' => 'WikkaUser',
            'email' => 'wikka_user@wikkawiki.org',
        ),
        array(
            'name' => 'WikkaAdmin',
            'email' => 'wikka_admin@wikkawiki.org',
        )
    );

    static public function init() {
        $model = new UserModel();
        WikkaModelFixture::init_table($model);
        WikkaModelFixture::init_fixture_data($model, self::$data);
        return $model;
    }
}


class PageModelFixture extends WikkaModelFixture {

    static public $data = array(
        array(
            'tag' => 'WikkaPage',
            'owner' => 'WikkaOwner',
            'user' => 'WikkaOwner',
            'title' => 'Wikka Page',
            'body' => 'Whan that WikkaPage with his shoures soote...',
            'note' => 'version 1'
        ),
        array(
            'tag' => 'WikkaPage',
            'owner' => 'WikkaOwner',
            'user' => 'WikkaUser',
            'title' => 'Wikka Page',
            'body' => 'Whan that WikkaPage with his shoures soote (updated!)',
            'note' => 'version 2'
        ),
        array(
            'tag' => 'WikkaPage',
            'owner' => 'WikkaOwner',
            'user' => 'WikkaUser',
            'title' => 'Wikka Page',
            'body' => 'Whan that WikkaPage with his shoures sooty!',
            'note' => 'version 3'
        ),
    );

    static public function init() {
        $model = new PageModel();
        WikkaModelFixture::init_table($model);
        WikkaModelFixture::init_fixture_data($model, self::$data);
        return $model;
    }
}


class CommentModelFixture extends WikkaModelFixture {

    static public $data = array(
        'user_name' => 'WikkaCommentor',
        'page_tag' => 'CommentBoard',
        'comments' => array(
            array(
                'text' => 'Parent Comment #1',
                'children' => array(
                    array(
                        'text' => 'Child #1 of Parent Comment #1',
                    ),
                    array(
                        'text' => 'Child #2 of Parent Comment #1',
                    )
                )
            ),
            array(
                'text' => 'Parent Comment #2',
                'children' => array(
                    array(
                        'text' => 'Child #1 of Parent Comment #2',
                        'children' => array(
                            array(
                                'text' => 'Grandchild #1 of Parent Comment #2',
                            )
                        )
                    )
                )
            )
        )
    );

    static public function init() {
        $model = new CommentModel();
        WikkaModelFixture::init_table($model);
        CommentModelFixture::init_comment_data();
        return $model;
    }

    static public function init_comment_data() {
        foreach ( self::$data['comments'] as $comment_data ) {
            self::save_comment_and_children($comment_data);
        }
    }

    static private function save_comment_and_children($comment_data, $parent_id=null) {
        $model = new CommentModel();
        $model->fields = array(
            'page_tag' => self::$data['page_tag'],
            'user' => self::$data['user_name'],
            'comment' => $comment_data['text']
        );

        if ( ! is_null($parent_id) ) {
            $model->fields['parent'] = $parent_id;
        }

        $query = $model->save();
        $parent_id = $model->pdo->lastInsertId('id');

        if ( isset($comment_data['children']) ) {
            foreach ( $comment_data['children'] as $child_data ) {
                self::save_comment_and_children($child_data, $parent_id);
            }
        }
    }
}


class AclModelFixture extends WikkaModelFixture {

    static public $data = array(
        array(
            'page_tag' => 'SecretPage',
            'read_acl' => '!NSA',
            'write_acl' => '!NSA',
            'comment_read_acl' => '!NSA',
            'comment_post_acl' => '!NSA',
        )
    );

    static public function init() {
        $model = new AccessControlListModel();
        WikkaModelFixture::init_table($model);
        WikkaModelFixture::init_fixture_data($model, self::$data);
        return $model;
    }
}
