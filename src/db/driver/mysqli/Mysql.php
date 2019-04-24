<?php

namespace j\db\driver\mysqli;

use j\db\driver\InterfaceDriver;
use j\db\sql\Quoter;
use j\db\exception\MysqlException;
use j\db\exception\ConnException;
use j\db\exception\QueryException;

use mysqli, mysqli_result;

/**
 * Class Mysql
 * @package j\db\driver\mysqli
 */
class Mysql implements InterfaceDriver{

    private $config = array();

    /**
     * @var mysqli
     */
    private $link;

    /**
     * 连接失败，最大重试次数
     * @var int
     */
    protected $connFailMaxTimes = 2;
    protected $connFailTimes = 0;

    /**
     * Mysql constructor.
     * @param $config
     */
    function __construct($config) {
        $this->config = $config;
        if(isset($config['connFailMaxTimes'])){
            $this->connFailMaxTimes = intval($config['connFailMaxTimes']);
        }
    }

    public function __destruct() {
        $this->close();
    }

    function close(){
        if (is_object($this->link)) {
            $this->link->close();
        }
        $this->link = null;
    }

    function getLink(){
        return $this->link;
    }


    /**
     * @param bool $force
     * @return bool
     * @throws ConnException
     */
    function connect($force = false) {
        if(!$force && isset($this->link) && $this->link){
            return true;
        }

        $this->link = mysqli_connect(
            $this->config['host'],
            $this->config['user'],
            $this->config['password'],
            isset($this->config['database']) ? $this->config['database'] : '',
            isset($this->config['port']) ? $this->config['port'] : ''
            );
        if(!$this->link || $this->link->connect_error){
            $this->link = null;
            if(mysqli_connect_errno() ==  2002){
                // Connection refused == 2002, 云网络存在不稳定状况
                $this->connFailTimes++;
                if($this->connFailTimes < $this->connFailMaxTimes){
                    return $this->connect(true);
                }

                // 抛出异常前复位错误连接次数
                $this->connFailTimes = 0;
            }
            throw(new ConnException(mysqli_connect_error(), 800));
        }

        if(isset($this->config['database'])){
            $rs = $this->link->select_db($this->config['database']);
            if(!$rs){
                $this->link = null;
                $msg = "Mysql error:" . $this->link->error;
                throw(new ConnException($msg, $this->link->errno));
            }
        }

        if(isset($this->config['charset'])){
            $this->link->query("SET NAMES " . $this->config['charset']);
        }

        return true;
    }

    /**
     * @param $sql
     * @return bool|ResultSet|mysqli_result
     * @throws MysqlException
     */
    function query($sql) {
        if(!$sql){
            throw(new QueryException("Empty sql"));
        }

        $this->connect();
        $rs = $this->link->query($sql);
        if ($rs === false) {
            $errno = $this->link->errno;
            $errmsg = $this->link->error;

            if (2006 == $errno) {  // mysql has gone
                $this->close();
                $this->connect(true);
                return $this->query($sql);
            }

            $e = new QueryException($errmsg, $errno);
            $e->sql = $sql;
            throw($e);
        }

        if ($rs instanceof mysqli_result) {
            return new ResultSet($rs, $sql);
        } else {
            return $rs;
        }
    }

    function lastId(){
        return $this->link->insert_id;
    }

    function getQuoter(){
        return Quoter::getInstance();
    }
}
