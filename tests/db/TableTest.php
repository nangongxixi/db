<?php
#DaoTest.php created by stcer@jz at 2017/4/10
namespace j\db;

use j\cache\File;
use j\di\Container;
use j\model\Model;
use PHPUnit\Framework\TestCase;

$_REQUEST['sqltest'] = 'jz';

/**
 * Class DaoTest
 * @package j\db
 */
class TableTest extends TestCase{
    /**
     *
     */
    protected function tearDown(){
        parent::tearDown();
    }

    /**
     *
     */
    protected function setUp(){
        parent::setUp();
    }

    /**
     * update info
     */
    function testFind(){
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

        $table = new Table('xyz_adminuser', $adapter);
        $table->resultAsObject(Model::class);

        $result = $table->find(['_limit' => 2]);
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertEquals(2, $profiler->count);

        $result = $table->find(['_limit' => 2]);
        $this->assertInstanceOf(ResultSetCached::class, $result);
        $this->assertEquals(2, $result->count());
        $this->assertEquals(2, $profiler->count);

        var_dump(file_get_contents($cacheFile));
        unlink($cacheFile);
    }
}

