<?php

namespace j\db\driver\pdo;

use j\db\ResultSetCached;

/**
 * Class ResultSet
 * @package j\db\driver\mysqli
 */
class PhpCpResultSet extends ResultSetCached
{
    /**
     * PhpCpResultSet constructor.
     * @param \PDOStatement $result
     * @param $sql
     * @param null $as_object
     */
    public function __construct($result, $sql, $as_object = NULL) {
        $result = $result->fetchAll(\PDO::FETCH_ASSOC);
        parent::__construct($result, $sql, $as_object);
    }

}

