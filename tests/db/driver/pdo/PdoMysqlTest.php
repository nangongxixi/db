<?php
# PhpCpTest.php
/**
 * User: Administrator
 * Date: 2017/5/16
 * Time: 8:24
 */
namespace j\db\driver\pdo;

use PHPUnit\Framework\TestCase;

/**
 * Class PhpCpTest
 * @package j\db\driver\pdo
 */
class PdoMysqlTest extends TestCase{

    /**
     * @var PdoMysql
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
        $this->pdo = new PdoMysql($conn);
    }

    function testQuery(){
        $sql = "select * from xyz_adminuser limit 2";

        $result = $this->pdo->query($sql);
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(count($result), 2);

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

//    function testRaw(){
//        echo "\n";
//        echo "Test raw\n";
//        $dsn = 'mysql:host=127.0.0.1;charset=utf8;port=3306;dbname=stcer';
//        $conn = new \PDO($dsn, 'root', '123123');
//        $sql = "select * from xyz_adminuser limit 2";
//        $result = $conn->query($sql);
//        echo "\n";
//        echo get_class($result) . "\n";
//
//        $this->assertEquals($result->rowCount(), 2);
//        foreach($result as $row){
//            var_dump($row['id']);
//        }
//
//        echo "Ag:\n";
//        foreach($result as $row){
//            var_dump($row['username']);
//        }
//    }
}