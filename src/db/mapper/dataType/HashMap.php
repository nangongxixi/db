<?php

namespace j\db\mapper\dataType;


use function var_dump;

/**
 * Class Integer
 * @package j\db\mapper\dateType
 */
class HashMap extends BaseAbstract {

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function store($value, array $attribute){
        return serialize($value);
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function restore($value, array $attribute){
        if(!$value){
            return [];
        }
        return unserialize($value);
    }
}
