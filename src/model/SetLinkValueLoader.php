<?php

namespace j\model;

use function call_user_func;
use Closure;
use function service;

/**
 * Class SetLinkOneValueLoader
 * 结果集一对一的关联据
 * @package jz\common\model
 */
class SetLinkValueLoader extends ValueLoaderAbstract {
    /**
     * @var string
     */
    protected $foreignKey;

    /**
     * @var
     */
    public $valueKey;

    /**
     * @var null
     */
    public $defaultValue = null;


    /**
     * 映射数据生成器, 生成mapData
     * @var callable
     */
    protected $mapDataMaker;

    /**
     * 映射数据
     * [fk => item, fk2 => item, ..]
     * @var array|\ArrayAccess
     */
    protected $mapData;

    /**
     * 过滤器
     * @var array
     */
    protected $filter = [];


    /**
     * @param $filed
     * @param $fk
     * @param $idMap
     * @param $dao
     * @param string $pk
     * @return SetLinkValueLoader
     */
    public static function createFromDao($filed, $fk, $idMap, $dao, $pk = '') {
        return new self(
            $filed, $fk,
            function() use ($idMap, $dao, $pk) {
                $dao = service($dao);
                $pk = $pk ?: $dao->primaryId;
                $cond[$dao->primaryId] = $idMap;
                return $dao
                    ->find($cond)
                    ->toArray($pk);
            });
    }

    /**
     * SetLinkOneValueLoader constructor.
     * @param string|array $filedName
     * @param callable $mapDataMaker
     * @param string $foreignKey
     */
    public function __construct($filedName, $foreignKey, callable $mapDataMaker = null){
        $this->mapDataMaker = $mapDataMaker;
        $this->foreignKey = $foreignKey;
        parent::__construct($filedName);
    }

    function addFilter($callback){
        $this->filter[] = $callback;
    }


    /**
     * @param $context
     * @param $key
     * @return mixed
     */
    protected function getFiledValue($context, $key){
        if(!isset($this->mapData)){
            $this->mapData = $this->getMapData();
        }

        if($this->filter){
             // 应用过滤器
             $rs = $this->getLinkValue($context, $key);
             foreach($this->filter as $filter){
                 $filter($rs, $context);
             }
             return $rs;
        } else {
            return $this->getLinkValue($context, $key);
        }

    }

    protected function getMapData()
    {
        if(!is_callable($this->mapDataMaker)){
            throw new \RuntimeException("Invalid map data");
        }

        return call_user_func($this->mapDataMaker);
    }

    private function getLinkValue($context, $key)
    {
        if(is_array($context) && !isset($context[$this->foreignKey])){
            return $this->defaultValue;
        }

        $index = $context[$this->foreignKey];
        if($this->mapData instanceof Closure){
            return call_user_func($this->mapData, $index);
        }

        if(isset($this->mapData[$index])){
            if(is_null($this->valueKey)){
                return $this->mapData[$index];
            } else {
                return $this->mapData[$index][$this->valueKey];
            }
        } else {
            return $this->defaultValue;
        }
    }
}
