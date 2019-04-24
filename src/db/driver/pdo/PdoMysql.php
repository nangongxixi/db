<?php

namespace j\db\driver\pdo;

use j\db\driver\InterfaceDriver;
use j\db\sql\Quoter;
use j\db\exception\ConnException;
use j\db\exception\QueryException;


/**
 * Class PdoMysql
 * @package j\db\driver\pdo
 */
class PdoMysql implements InterfaceDriver{

    /**
     * @var \PDO
     */
    protected $link;

    /**
     * @var string
     */
    protected static $pdoClass = \PDO::class;

    /**
     * @var string
     */
    protected static $resultSetClass = ResultSet::class;

    /**
     * @var array
     */
    private $config = array();

    /**
     * PhpCp constructor.
     * @param $config
     */
    function __construct($config) {
        $this->config = $config;
    }

    function close(){
    }

    private $reconnect = 0;

    /**
     * @param bool $force
     * @return bool
     * @throws ConnException
     */
    function connect($force = false) {
        if(!$force && isset($this->link) && $this->link){
            $errorNo = $this->link->errorCode();
            if(isset($errorNo)
                && $errorNo
                && ($errorNo ==  2002 || $errorNo ==  2006)){
                // mysql has gone away  ==  2006
                // Connection refused == 2002
            } else {
                return true;
            }
        }

        try{
            $port = isset($this->config['port']) ? $this->config['port'] : '';
            $dbName = isset($this->config['database']) ? $this->config['database'] : '';
            $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
            $dsn = "mysql:host={$this->config['host']};charset={$charset}";
            if($port){
                $dsn .= ";port={$port}";
            }
            if($dbName){
                $dsn .= ";dbname={$dbName}";
            }
            $this->link = new static::$pdoClass(
                $dsn, $this->config['user'], $this->config['password']
            );
        } catch(\Exception $e){
            throw(new ConnException($e->getMessage()));
        }

        return true;
    }


    /**
     * @param $sql
     * @param $isUpdate
     * @return ResultSet|bool
     * @throws \Exception
     */
    function query($sql, $isUpdate = null){
        if(!$sql){
            throw(new QueryException("Empty sql"));
        }

        $this->connect();
        if($isUpdate){
            $rs = $this->link->exec($sql . "");
        } else {
            $rs = $this->link->query($sql . "");
        }

        if ($rs === false) {
            $errno = $this->link->errorCode();
            $errmsg = $this->link->errorInfo();
            if (2006 == $errno) {
                // mysql has gone
                $this->close();
                $this->connect(true);
                return $this->query($sql, $isUpdate);
            }

            $e = new QueryException($errmsg, $errno);
            $e->sql = $sql;
            throw($e);
        }
        $this->reconnect = 0;
        if($isUpdate){
            return $rs;
        } else {
            return new static::$resultSetClass($rs, $sql);
        }
    }

    function lastId(){
        return $this->link->lastInsertId();
    }

    function getQuoter(){
        return Quoter::getInstance();
    }
}