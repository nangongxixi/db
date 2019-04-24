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
class PhpCpTest extends TestCase{

    /**
     * @var PhpCp
     */
    protected $pdo;

    protected function setUp(){
        $_REQUEST['sqltest'] = 'jz';
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
        $this->pdo = new PhpCp($conn);
    }

    function testQuery(){
        $sql = "select * from xyz_adminuser limit 1";

        $result = $this->pdo->query($sql);
        $this->assertInstanceOf(PhpCpResultSet::class, $result);
        $this->assertEquals(count($result), 1);

        foreach($result as $row){
            var_dump($row);
        }

        foreach($result as $row){
            var_dump($row);
        }

        $dsn = "mysql:host=127.0.0.1;charset=utf8;port=3306;dbname=stcer";
        $pdo = new \pdoProxy($dsn, 'root', '123123');
        $sql = "select * from xyz_adminuser limit 1";
        $result  = $pdo->query($sql);
        $this->assertInstanceOf(\pdo_connect_pool_PDOStatement::class, $result);
        $this->assertEquals(count($result), 1);
    }
}