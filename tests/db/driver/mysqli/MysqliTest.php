<?php

namespace j\db\driver\mysqli;

use j\db\setDecorator\HasOneLinkedValueLoader;
use j\db\setDecorator\SetValueManager;
use j\db\Table;
use j\model\ValueManager;
use PHPUnit\Framework\TestCase;

/**
 * Class PhpCpTest
 * @package j\db\driver\pdo
 */
class MysqliTest extends TestCase{
    /**
     * @var Mysql
     */
    protected $pdo;

    protected function setUp(){
        $_REQUEST['sqltest'] = 'jz';
        $conn = [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'driver' => 'pdo',
            'password' => '123123',
            'database' => 'stcer',
            'charset' => 'utf8',
            'persitent' => false
        ];
        $this->pdo = new Mysql($conn);
    }

    protected function getQueryResult(){
        $sql = "select * from xyz_adminuser limit 2";
        return $this->pdo->query($sql);
    }

    function testQuery(){
        $result = $this->getQueryResult();
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(count($result), 2);

        // test result
        $i = 0;
        foreach($result as $row){
            $i++;
            $this->assertTrue($row['id'] > 0);
        }
        $this->assertEquals($i, 2);

        $i = 0;
        foreach($result as $row){
            $i++;
            $this->assertTrue($row['id'] > 0);
        }
        $this->assertEquals($i, 2);
    }

    function testResultSet(){
        $result = $this->getQueryResult();

        $loader = $this->getSetValueLoader();
        $loader->decorate($result);

        foreach($result as $row){
            $this->assertTrue($row['test'] == $row['id'] . '-test');
            $this->assertTrue($row['test1'] == $row['id'] . '-test1');
            $this->assertTrue(is_array($row['link']));
            $this->assertTrue($row['link']['id'] == $row['id']);
        }
    }


    private function getSetValueLoader(){
        // test extend
        $loader = new SetValueManager();
        $loader->addLoader(function($row, $key){
            return $row['id'] . '-' . $key;
            }, ['test']);
        $loader->addLoader(function($row, $key){
            return $row['id'] . '-' . $key;
            }, ['test1']);

        // has one link
        $link = new HasOneLinkedValueLoader(
            Table::factory('xyz_adminuser'),
            'link',
            'id'
            );
        $loader->addLoader($link);

        return $loader;
    }
}

