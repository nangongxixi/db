<?php
#DaoTest.php created by stcer@jz at 2017/4/10
namespace j\db;

use j\cache\File;
use j\db\profiler\InterfaceProfiler;
use j\di\Container;
use j\model\Model;
use PHPUnit\Framework\TestCase;

$_REQUEST['sqltest'] = 'jz';

/**
 * Class DaoTest
 * @package j\db
 */
class AdapterTest extends TestCase{

    protected function tearDown(){
        parent::tearDown();
    }

    protected function setUp(){
        parent::setUp();
    }

    /**
     * update info
     */
    function testQuery(){
        /** @var Adapter $adapter */
        $adapter = Container::getInstance()->get('dbAdapter');
        $sql = "select * from xyz_adminuser limit 2";
        $result = $adapter->query($sql);
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /**
     *  @depends testQuery
     */
    function testQueryCache(){
        /** @var Adapter $adapter */
        $adapter = Container::getInstance()->get('dbAdapter');
        $cacheFile = __DIR__ . "/_tmp/cache.log";
        if(file_exists($cacheFile)){
            unlink($cacheFile);
        }
        $cache = new File(['file' => $cacheFile]);
        $adapter->setCache($cache, true);

        $profiler = new TmpProfiler();
        $adapter->setProfiler($profiler);

        $sql = "select * from xyz_adminuser limit 2";
        $result = $adapter->query($sql);
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(1, $profiler->count);

        $sql = "select * from xyz_adminuser limit 2";
        $result = $adapter->query($sql);
        $this->assertInstanceOf(ResultSetCached::class, $result);
        $this->assertEquals(2, $result->count());
        $this->assertEquals(1, $profiler->count);

        unlink($cacheFile);
    }
}
