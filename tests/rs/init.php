<?php

namespace j\db\test;

require __DIR__ . '/../boot.php';
require __DIR__ . '/demoDao.php';

config()->set([
    'db.conn' => [
        'database' => 'test',
        'host' => '127.0.0.1',
        'password' => ''
    ]
]);
