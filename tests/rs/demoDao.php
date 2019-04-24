<?php

namespace j\db\test;

use j\db\Dao;
use j\db\setDecorator\HasOneLinkedValueLoader;
use j\db\setDecorator\SetValueManager;
use j\event\InterfaceListen;
use j\model\Model;

/**
 * Class TestDao
 * @package j\db\test
 * @method Model[] find(array $where = array(), $extends = null)
 */
class TestDao extends Dao
{
    public function getTable() {
        $table = table('test');
        $table->resultAsObject(Model::class);
        return $table;
    }

    protected $extends;

    /**
     * @inheritdoc
     */
    protected function getListSetDecorator($set, $where) {
        $detail = new HasOneLinkedValueLoader(
            TestDetailDao::getInstance(),
            'detail', 'id', 'test_id'
            );
        $detail->setChildField('content');
        return $this->getValueManger([$detail]);
    }

    /**
     * 自动更新密码表、第三方用户登录表
     * @return array
     */
    protected function behaviors(){
        return [
            TestDetailDao::class,
        ];
    }
}


class TestDetailDao extends Dao implements InterfaceListen
{
    public function getTable() {
        $table = table('test_detail');
        $table->setPrimkey('test_id');
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
        $profile = $this->findOne($id, [], true, [ 'test_id' => $id]);
        $profile->exchange($data);
        return $this->save($profile);
    }
}
