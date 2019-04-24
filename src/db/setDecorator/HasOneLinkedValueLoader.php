<?php

namespace j\db\setDecorator;

use j\db\Dao;
use j\db\exception\RuntimeException;
use j\db\ResultSet;
use j\db\SetDecoratorInterface;
use j\db\Table;
use j\model\Model;
use j\model\SetLinkValueLoader;
use j\model\ValueLoaderAbstract;
use j\model\ValueManager;

/**
 * 主要解决 m x n次sql查询
 *
 * Class HasOneLinkedValueLoader
 * @package j\db\setDecorator
 */
class HasOneLinkedValueLoader
    extends SetLinkValueLoader implements SetDecoratorInterface

{
    /**
     * @var Dao|Table
     */
    protected $dao;

    /**
     * @var string
     */
    public $primaryKey;

    /**
     * @var array
     */
    public $cond = [];


    /**
     * LinkDao constructor.
     * @param string $fieldName
     * @param string $foreignKey
     * @param string $primaryKey
     * @param string $valueKey
     * @param Dao|Table $dao
     */
    public function __construct(
        $dao, $fieldName, $foreignKey,
        $primaryKey = 'id',
        $valueKey = null
    ){
        $this->dao = $dao;
        $this->primaryKey = $primaryKey;
        $this->valueKey = $valueKey;

        parent::__construct($fieldName, $foreignKey);
    }

    /**
     * 设置mapData主查询条件
     * @param ResultSet $resultSet
     * @param bool $bindModel
     */
    function decorate(ResultSet $resultSet, $bindModel = false) {
        $ids = $resultSet->toArray(null, $this->foreignKey, false, true);
        if(!$ids){
            return;
        }
        $this->cond[$this->primaryKey] = $ids;
    }

    protected function getMapData()
    {
        if(!isset($this->cond[$this->primaryKey])){
            throw new RuntimeException("Link condition is empty");
        }

        return $this->dao
            ->find($this->cond)
            ->toArray($this->primaryKey);
    }
}