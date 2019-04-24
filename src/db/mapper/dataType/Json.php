<?php

namespace j\db\mapper\dataType;

use j\tool\Strings;
use j\db\mapper\DataTypeInterface;

/**
 * Class Integer
 * @package j\db\mapper\dateType
 */
class Json extends BaseAbstract {

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function store($value, array $attribute){
        if(!$value){
            return null;
        }

        if(isset($attribute['charset']) && $attribute['charset'] == 'gbk'){
            $value = Strings::utf8($value, $attribute['charset']);
        }

        return json_encode($value);
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
        $value = json_decode($value, true);
        if(isset($attribute['charset']) && $attribute['charset'] == 'gbk'){
            $value = Strings::toGbk($value);
        }
        return $value;
    }
}