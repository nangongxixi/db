<?php

namespace j\db\driver\mysql;

use Exception;
use j\db\driver\InterfaceDriver;
use j\db\sql\QuoterInterface;
use j\db\sql\Quoter;

use j\db\exception\ConnException as MysqlConnException;
use j\db\exception\QueryException as MysqlQueryException;

/**
 * Class Mysql
 * @package j\db\driver\mysql
 * @deprecated 5.5
 */
class Mysql implements InterfaceDriver{
    private $config = array();
    private $link;

    function __construct($config) {
        trigger_error("j.db.driver.mysql is deprecated!");
        $this->config = $config;
    }
    
    public function __destruct() {
        if (is_resource($this->link)) {
            mysql_close($this->link);
        }
    }
    
    function connect() {
        if(is_resource($this->link)){
            return true;
        }
        
        $this->link = mysql_connect(
            $this->config['host'],
            $this->config['user'],
            $this->config['password']
            );
        if(!$this->link) 
            throw(new MysqlConnException('db error:'. mysql_error() . "\n"));

        if(isset($this->config['database']))
            mysql_select_db($this->config['database'], $this->link);
        if(isset($this->config['charset']))
            mysql_query("SET NAMES " . $this->config['charset'], $this->link);
    }

    /**
     * @param $sql
     * @return ResultSet|resource
     * @throws \Exception
     */
    function query($sql) {
        $this->connect();
        $rs = mysql_query($sql, $this->link);
        if($rs === FALSE){
            $errno = mysql_errno($this->link);
            $errmsg = mysql_error($this->link);
            throw(new Exception("sql : {$sql}\n, {$errno}:{$errmsg}\n"));
        }elseif(is_resource($rs)){
            return new ResultSet($rs, $sql);
        }else{
            return $rs;
        }
    }
    
    function lastId(){
        return mysql_insert_id($this->link);
    }

    function getQuoter(){
        return Quoter::getInstance();
    }
}