<?php

namespace j\db;

use j\db\driver\pdo\PhpCp;
use j\db\driver\pdo\PdoMysql;
use j\db\profiler\InterfaceProfiler;
use j\db\driver\mysql\Mysql;
use j\db\driver\InterfaceDriver;
use j\db\driver\mysqli\Mysql as Mysqli;
use j\db\sql\Delete;
use j\db\sql\Insert;
use j\db\sql\Update;

/**
 * Class Adapter
 * @package j\db
 */
class Adapter{
    /**
     * @param $conf array
     * @return Adapter
     * @throws \Exception
     */
    static function factory($conf) {
        static $instances;
        $key = serialize($conf);
        if(isset($instances[$key])) {
            return $instances[$key];
        }

        $driver = null;
        if(isset($conf['driver'])){
            if($conf['driver'] instanceof InterfaceDriver || is_array($conf['driver'])) {
                $driver = $conf['driver'];
            }
        }

        if(!$driver && isset($conf['conn'])) {
            $driver = self::createDbConn($conf['conn']);
        }else{
            throw new \Exception('Driver is null');
        }

        $profiler = null;
        if(isset($conf['profiler'])){
            $profiler = $conf['profiler'] ?: $profiler;
        }

        $instances[$key] = new Adapter($driver, $profiler);
        return $instances[$key];
    }

    /**
     * @param $conn
     * @return Mysql|Mysqli
     */
    static function createDbConn($conn) {
        $type = isset($conn['driver']) ? $conn['driver'] : 'mysqli';
        if('mysql' == strtolower($type)){
            $driver = new Mysql($conn);
        } elseif($type == 'pdo'){
            $driver = new PdoMysql($conn);
        } elseif($type == 'phpcp'){
            $driver = new PhpCp($conn);
        } else {
            $driver = new Mysqli($conn);
        }
        return $driver;
    }

    /**
     * mysql connection
     * @var driver\mysqli\Mysql[]|InterfaceDriver[]
     */
    protected $driver = array();

    /**
     * @var Profiler\InterfaceProfiler
     */
    protected $profiler = null;

    /**
     * @var bool
     */
    protected $cacheEnable = false;

    /**
     * @var int
     */
    protected $cacheExpire = 3600;

    /**
     * @var bool flush cache
     */
    protected $cacheFlush = false;


    /**
     * @var \j\cache\Base
     */
    protected $cache = null;


    /**
     * @param InterfaceDriver|array $driver
     * @param Profiler\InterfaceProfiler $profiler
     */
    public function __construct($driver, InterfaceProfiler $profiler = null) {
        if(!is_array($driver)){
            $driver = ['r' => $driver, 'w' => $driver];
        } elseif(!isset($driver['w'])){
            throw new exception\RuntimeException("Invalid read dbDriver");
        } elseif(!isset($driver['r'])) {
            $driver['r'] = $driver['w'];
        }

        $this->driver = $driver;
        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }


    /**
     * @param \j\cache\Base $cache
     * @param int $expire
     * @param boolean $enable
     * @param boolean $flush
     */
    public function setCache($cache, $enable = null, $expire = 0, $flush = null) {
        $this->cache = $cache;

        if($expire){
            $this->cacheExpire = $expire;
        }

        if($enable !== null){
            $this->cacheEnable = $enable;
        }

        if($flush !== null){
            $this->cacheFlush = $flush;
        }
    }

    /**
     * @param InterfaceProfiler $profiler
     * @return $this
     */
    public function setProfiler(InterfaceProfiler $profiler = null) {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * @param $sql
     * @param $type
     * @param array $options, []
     * @return \j\db\ResultSet|\j\db\driver\mysqli\ResultSet
     */
    public function query($sql, $type = null, $options = []) {
        if($sql instanceof sql\Select
            || is_string($sql) && 0 === stripos(trim($sql), 'select')
            || $type == SqlFactory::SELECT
        ){
        // select sql
            if($this->cacheEnable
                && !(isset($options['disableCache']) && $options['disableCache'])
                && $this->cache
            ){
            // can cache
                $cacheKey = 'sql_' . md5($sql);
                if(!$this->cacheFlush){
                // read from cache
                    $rs = $this->cache->get($cacheKey);
                    if($rs !== null){
                        $rs = new ResultSetCached($rs, $sql);
                        return $rs;
                    }
                }
            }

            $driver = $this->driver['r'];
        } else {
            $driver = $this->driver['w'];
        }

        // execute sql {
        if($this->profiler){
            $this->profiler->profilerStart($sql);
        }

        if(isset($_REQUEST['sqltest']) && $_REQUEST['sqltest'] == 'jz'){
            echo $sql . "<br />\n";
        }

        $isUpdate = $sql instanceof Insert || $sql instanceof Delete || $sql instanceof Update;
        $rs = $driver->query($sql, $isUpdate);

        if($this->profiler){
            $this->profiler->profilerFinish();
        }
        // } end execute

        if(isset($cacheKey) && $rs->count() < 5000){
            $this->cache->set($cacheKey, $rs->toArray(), $this->cacheExpire);
        }

        if($sql instanceof sql\Insert){
            if($rs){
                $insertId = $this->driver['w']->lastId();
                $rs = $insertId ?: $rs;
            }
        }
        return $rs;
    }

    /**
     * @return InterfaceDriver
     */
    public function getQuoter(){
        return $this->driver['r']->getQuoter();
    }
}
