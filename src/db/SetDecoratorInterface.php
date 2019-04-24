<?php
# SetDecoratorInterface.php

namespace j\db;

/**
 * Interface SetDecoratorInterface
 * @package j\db
 */
interface SetDecoratorInterface {

    function decorate(ResultSet $resultSet);

}