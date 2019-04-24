<?php

namespace j\db\driver\pdo;


/**
 * Class PdoMysql
 * @package j\db\driver\pdo
 */
class PhpCp extends PdoMysql{

    /**
     * @var string
     */
    protected static $pdoClass = \pdoProxy::class;

    /**
     * @var string
     */
    protected static $resultSetClass = PhpCpResultSet::class;

    /**
     * @param $sql
     * @param $isUpdate
     * @return ResultSet|bool
     * @throws \Exception
     */
    function query($sql, $isUpdate = null){
        $result = parent::query($sql, $isUpdate);
        $this->link->release();
        return $result;
    }
}