<?php

namespace j\db\relation;

use j\db\Table;
use j\model\Model;

/**
 * Class RelationHasOne
 * @package j\db\relation
 */
class HasOne extends Base {
    /**
     * @param Model $model
     * @param [] $options
     * @return \j\db\driver\mysqli\ResultSet
     */
    function load($model, $options = []){
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        return $dao->findOne($cond);
    }
}