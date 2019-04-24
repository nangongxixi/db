<?php
# pdo.php

use j\db\Adapter;
use j\db\driver\pdo\PhpCp;
use j\db\UserDao;
use j\model\Model;
use pdoProxy;

$_REQUEST['sqltest'] = 'jz';
require __DIR__ . '/boot.php';

class __TEST_DB {
    function testSelect0() {
        $dsn = "mysql:host=127.0.0.1;charset=utf8;port=3306;dbname=stcer";
        try{
            $pdo = new pdoProxy($dsn, 'root', '123123');
            $sql = "select * from xyz_adminuser limit 1";
            $stem = $pdo->query($sql);
            foreach($stem as $row){
                var_dump($row);
            }
        } catch(Exception $e){
            throw(new Exception($e->getMessage()));
        }
    }

    function testSelect1(){
        $conn = [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'driver' => 'phpcp',
            'password' => '123123',
            'database' => 'stcer',
            'charset' => 'utf8',
            'persitent' => false
        ];
        $pdo = new PhpCp($conn);
        $sql = "select * from xyz_adminuser limit 1";
        $stem = $pdo->query($sql);
        foreach($stem as $row){
            var_dump($row);
        }
    }

    function testSelect2(){
        $conn = [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'driver' => 'phpcp',
            'password' => '123123',
            'database' => 'stcer',
            'charset' => 'utf8',
            'persitent' => false
        ];
        $pdo = Adapter::factory([
            'conn' => $conn
        ]);
        $sql = "select * from xyz_adminuser limit 1";
        $stem = $pdo->query($sql);
        foreach($stem as $row){
            var_dump($row);
        }
    }

    function testInsert(){
        $dao = new UserDao();
        $data = [
            'username' => md5(uniqid()),
            'password' => "123123",
            'usergroup' => 1,
        ];
        $model = new Model($data, true);
        $id = $dao->insert($model);
        var_dump($id);
    }

    function testInsert2(){
        $dsn = "mysql:host=127.0.0.1;charset=utf8;port=3306;dbname=stcer";
        $pdo = new \pdoProxy($dsn, 'root', '123123');
        $username = uniqid();
        $sql = "INSERT INTO `xyz_adminuser` (`username`, `password`, `usergroup`) VALUES ('{$username}', '123123', 1)";
        $rs = $pdo->exec($sql);
        var_dump($rs);
    }
}

$test = new __TEST_DB();
$test->testInsert();
echo "\n";