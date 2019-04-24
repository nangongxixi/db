<?php

namespace j\db\test;

use j\base\Config;
use j\db\ServiceProvider as DBProvider;
use j\db\Table;
use j\model\ServiceProvider as ModelProvider;
use j\di\Container;

function config($key = null, $def = null){
    if(!isset($key)){
        return Config::getInstance();
    } else {
        return Config::getInstance()->get($key, $def);
    }
}

function table($name){
    return Table::factory($name);
}

$_REQUEST['sqltest'] = 'jz';
$loader = include dirname(__DIR__) . "/vendor/autoload.php";

/** @var \Composer\Autoload\ClassLoader $loader */
$loader->addPsr4("j\\db\\", __DIR__ . "/db");

$config = Config::getInstance();
$config->set('db', [
    // 数据库连接配置
    'conn' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '123123',
        'database' => 'stcer',
        'charset' => 'utf8',
        'persitent' => false
    ],
]);

$di = Container::getInstance();
$di->set('config', $config);
$di->registerProviders([
    DBProvider::class,
    ModelProvider::class
]);
