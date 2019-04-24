<?php

namespace j\model;

use ArrayAccess;

/**
 * Class ValueLoaderAbstract
 * @package j\db\setDecorator
 */
abstract class ValueLoaderAbstract implements ValueLoaderInterface
{
    /**
     * 值的主键, 可用于alias 或 child
     * @var string
     */
    protected $fieldName;

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $aliasFields = [];

    /**
     * @var array
     */
    protected $childFields = [];


    /**
     * ValueLoaderAbstract constructor.
     * @param string|array $fieldName
     * @param array $aliasFields
     * @param array $childFields
     */
    public function __construct($fieldName,
        array $aliasFields = [],
        array $childFields = []
    ){
        if(is_array($fieldName)){
            $this->keys =  $fieldName;
            $this->fieldName = array_shift($fieldName);
        } else {
            $this->fieldName = $fieldName;
            $this->keys = [$fieldName];
        }

        if($aliasFields){
            $this->setAlias($aliasFields);
        }

        if($childFields){
            $this->setChildField($childFields);
        }
    }

    /**
     * @param array $fields
     */
    public function setAlias(array $fields)
    {
        $this->aliasFields = $fields;
    }

    public function setChildField($key, $field = null)
    {
        if(is_array($key)){
            foreach($key as $_k => $v){
                if(is_numeric($_k)){
                    $_k = $v;
                }
                $this->setChildField($_k, $v);
            }
        } else {
            if(is_null($field)){
                $field = $key;
            }
            $this->childFields[$key] = $field;
        }
    }

    /**
     * @param $key
     * @param Model|array $context
     * @return bool
     */
    function isCanLoad($context, $key) {
        if($key == $this->fieldName
            || in_array($key, $this->childFields)
            || in_array($key, $this->aliasFields)
            || in_array($key, $this->keys)
        ){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    function getKeys()
    {
        return array_merge($this->aliasFields, $this->childFields, $this->keys);
    }

    /**
     * @param array|Model $context
     * @param string $key
     * @return mixed|null
     */
    function loadValue($context, $key)
    {
        if($key == $this->fieldName || in_array($key, $this->keys)){
            return $this->getFiledValue($context, $key);
        } else {
            $parentKey = $this->fieldName;
            if(in_array($key, $this->aliasFields)){
                return $context[$parentKey];
            }elseif(in_array($key, $this->childFields) && $context[$parentKey]){
                $childField = $this->childFields[$key];
                return $context[$parentKey][$childField];
            } else {
                return null;
            }
        }
    }

    abstract protected function getFiledValue($context, $key);


    public function __invoke()
    {
        return call_user_func_array(array($this, 'loadValue'), func_get_args());
    }
}