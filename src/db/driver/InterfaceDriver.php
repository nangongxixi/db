<?php

namespace j\db\driver;

/**
 * Interface InterfaceDriver
 * @package j\db\driver
 */
interface InterfaceDriver{
    /**
     * @param $sql
     * @return \j\db\driver\mysql\ResultSet
     */
    function query($sql);
    function lastId();
    function getQuoter();
}