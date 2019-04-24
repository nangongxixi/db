<?php

namespace j\db\relation;

use j\db\Table;
use j\model\Model;

/**
 * Class RelationHasMany
 * load()
 * add()
 * update()
 * delete()
 * reset()
 * @package j\db\relation
 */
class HasMany extends Base {

    /**
     * @param Model $model
     * @param [] $options
     * @return \j\db\driver\mysqli\ResultSet
     */
    function load($model, $options = []){
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        return $dao->find($cond);
    }

    /**
     * @param $model
     * @param array $options
     * @return string
     * @throws \Exception
     */
    function count($model, $options = []) {
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        return $dao->count($cond);
    }

    /**
     * @param $model
     * @param $options
     * @return string
     */
    function remove($model, $options) {
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        return $dao->delete($cond);
    }

    /**
     * @param $model
     * @param $options
     * @param $data
     */
    function add($model, $data, $options = []) {
        foreach($data as $item){
            $item[$this->keyMap[0]] = $model[$this->keyMap[1]];
        }
    }

    /**
     * @param $model
     * @param $data
     */
    function update($model, $data){
        
    }

    /**
     * @param $model
     * @param $data
     */
    function reset($model, $data){
        
    }
}