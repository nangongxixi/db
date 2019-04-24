<?php

namespace j\db\test;

use j\db\Adapter;
use j\db\Dao;
use j\db\driver\mysqli\Mysql;
use j\db\setDecorator\HasOneLinkedValueLoader;
use j\db\setDecorator\SetValueManager;
use j\db\Table;
use j\debug\Profiler;
use j\event\InterfaceListen;
use j\model\Model;

require __DIR__ . '/../boot.php';

config()->set([
    'db.conn' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'driver' => 'mysqli',
        'password' => '123123',
        'database' => 'newjc001',
        'charset' => 'utf8',
        'persitent' => false
    ]
]);

/**
 * Class TestDao
 * @package j\db\test
 * @method Model[] find(array $where = array(), $extends = null)
 */
class __NewsDao extends Dao
{
    public function getTable() {
        $table = table('news');
        $table->resultAsObject(Model::class);
        return $table;
    }

    protected $extends;

    /**
     * @inheritdoc
     */
    protected function getListSetDecorator($set, $where) {
        $detail = new HasOneLinkedValueLoader(
            __NewsDetailDao::getInstance(),
            'detail', 'id', 'news_id'
        );
        $detail->setChildField(['content']);

        $test = [
            function($row, $key){
                return $row['id'] . '-' . $key;
            },
            ['test']
        ];

        $test1 = [
            function($row, $key){
                return $row['id'] . '-' . $key;
            },
            ['test1']
        ];
        return $this->getValueManger([$detail, $test, $test1]);
    }

    /**
     * 自动更新密码表、第三方用户登录表
     * @return array
     */
    protected function behaviors(){
        return [
            __NewsDetailDao::class,
        ];
    }
}


class __NewsDetailDao extends Dao implements InterfaceListen
{
    public function getTable() {
        $table = table('news_detail');
        $table->setPrimkey('news_id');
        return $table;
    }

    /**
     * @param TestDao $em
     */
    public function bind($em) {
        $em->on($em::EVENT_INSERT_AFTER, array($this, 'onSave'));
        $em->on($em::EVENT_UPDATE_AFTER, array($this, 'onSave'));
        $em->on($em::EVENT_DELETE_AFTER, function($e, $model){
            /** @var Model $model */
            return $this->table->delete($model->identifier());
        });
    }

    /**
     * @param Model $model
     * @param array $data
     * @return bool
     */
    public function onSave($e, $model, $data){
        if(!isset($data['content'])) {
            return true;
        }

        $id = $model->identifier();
        $profile = $this->findOne($id, [], true, [ 'news_id' => $id]);
        $profile->exchange($data);
        return $this->save($profile);
    }
}

function assert_exp($exp){
    echo $exp ? 'TRUE' : 'FALSE';
    echo "\n";
}

function getResult(){
    return __NewsDao::getInstance()->find(['_limit' => 10]);
}

/**
 * main
 */
Profiler::start();
$_REQUEST['sqltest'] = 'jz';
foreach(getResult() as $row){
    assert_exp($row['test'] == $row['id'] . '-test');
    assert_exp($row['test1'] == $row['id'] . '-test1');
//    assert_exp(is_array($row['detail']));
//    assert_exp($row['detail']['news_id'] == $row['id']);
    echo "\n";
}
Profiler::stop();