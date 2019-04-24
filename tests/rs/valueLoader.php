<?php

namespace j\db\test;

require __DIR__ . '/init.php';

/**
 * @return array
 */
function __genInfo(){
    return [
        'title' => 'test title ' . rand(1, 100),
        'phone' => 18909876 . rand(100, 999),
        'create_date' => date('Y-m-d H:i:s'),
        'age' => rand(20, 80),
        'content' => "the body for test " . rand(1000, 9999)
    ];
}

function __testInit($n = 20){
    $dao = TestDao::getInstance();
    for($i = 0; $i < $n; $i++){
        $id = $dao->insert(__genInfo());
        echo "Insert id {$id} success\n";
    }
}

function __testUpdate(){
    $dao = TestDao::getInstance();
    $list = $dao->find(['_limit' => 20]);
    foreach($list as $item){
        $item->exchange(__genInfo());
        $item->save();
    }
}

if(isset($argv[1])){
    switch ($argv[1]){
        case 'init' :
            __testInit();
            break;
        case 'update':
            __testUpdate();
            break;
        default:
            echo "Invalid action, valid:init|update\n";
    }
    exit;
}

function __testSelect(){
    $dao = TestDao::getInstance();
    $list = $dao->find(['_limit' => 20]);
    foreach($list as $item){
        echo $item['id'] . ":";
        echo $item['content'] . "\n";
    }
}

__testSelect();