<?php

namespace j\db\sql;

use PHPUnit\Framework\TestCase;

class SqlAbstractTest extends TestCase{
    function test_compile_conditions(){
        $sql = new Select();
        $sql->setQuoter(Quoter::getInstance());

        $sql->setTable('test');
        $sql->where('id', '=', 1);

        $this->assertEquals($sql->compile(), "SELECT * FROM `test` WHERE `id` = 1");

        $sql->where('id', 'not in', [1, 2]);
        $this->assertEquals($sql->compile(), "SELECT * FROM `test` WHERE `id` = 1 AND `id` NOT IN (1, 2)");
    }
}