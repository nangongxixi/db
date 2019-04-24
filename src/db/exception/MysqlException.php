<?php

namespace j\db\exception;

use Exception;

/**
 * Class MysqlException
 * @package j\db\exception
 */
class MysqlException extends Exception {
    public $sql;
}