<?php

namespace j\mongo;

use MongoDate;
use MongoMaxKey;
use MongoCursor;

/**
 * Class TableTest
 * @package j\view\tag
 */
class TableTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Table
     */
    protected $table;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->table = new Table("user", __NAMESPACE__ . "\\User");
        //$this->table = new Table("user");
    }

    /**
     * @var
     */
    protected $lastInsertId;

    /**
     *
     */
    function testInsert(){
        $user = new User();
        $user->exchange([
            'username' => "ster1",
            'email' => "test2@163.com",
            'age' => 12,
            'ip' => "127.0.0.1",
            'cdate' =>  new MongoDate(strtotime("2010-01-30 00:00:00")),
            'docBy' =>  new MongoMaxKey,
            'token' =>  md5(time()),
            ]);

        $rs = $this->table->insert($user);
        $this->assertTrue($rs);
        $this->assertTrue(strlen($user->identifier()) > 10);
        $this->lastInsertId = $user->identifier();
    }

    /**
     * @depends testInsert
     */
    function testFind() {
        $list = $this->table->find([
            //'username' => 'stcer1'
            ], ['offset' => 10]);
        $this->assertFalse($list instanceof MongoCursor);
        $this->assertTrue($list instanceof QuerySet);
        $this->assertTrue($list->count() > 1);
    }


    /**
     * @depends testInsert
     */
    function testUpdate(){
        /** @var User $user */
        $user = $this->table->findOne($this->lastInsertId);
        if(!$user){
            return;
        }

        $user->username = $username = "Update" . rand();
        $rs = $this->table->update($user);

        $user = $this->table->findOne($this->lastInsertId);
        $this->assertTrue($user->username == $username);
        $this->assertTrue($rs);
    }

    /**
     * @depends testUpdate
     */
    function testUpdateSet(){
        /** @var User $user */
        $user = $this->table->findOne($this->lastInsertId);
        if(!$user){
            return;
        }

        $set = [
            'username' => "test update set " . rand(),
            'cdate' => new MongoDate(strtotime("2015-01-30 00:00:00")),
            ];
        $rs = $this->table->updateSet($user->identifier(), $set);
        $this->assertTrue($rs);

        $user = $this->table->findOne($this->lastInsertId);
        $this->assertTrue($user->username == $set['username']);
    }

    /**
     * @depends testUpdateSet
     */
    function testRemove(){
        $list = $this->table->find([
            //'username' => 'stcer1'
        ], ['limit' => 10]);

        $list->rewind();
        $info = $list->getNext();
        $rs = $this->table->remove($info);
        $this->assertTrue($rs);
    }
}


/**
 * Class User
 * @package j\mongo
 * @property string $username
 * @property string $ip
 * @property string $email
 * @property string $cdate
 */
class User extends Model {

}