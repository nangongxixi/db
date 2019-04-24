<?php

namespace j\db\mapper\dataType;

use j\tool\ArrayUtils;

/**
 * Class SplitArray
 * @package j\db\mapper\dateType
 */
class SplitArray extends BaseAbstract{

    const SPLIT_CHAR = ',';

    protected function getSeparator($attrs){
        return ArrayUtils::gav($attrs, 'separator', self::SPLIT_CHAR);
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function store($value, array $attribute){
        if(!$value){
            return null;
        }

        return implode($this->getSeparator($attribute), $value);
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed|array
     */
    public function restore($value, array $attribute){
        if(!$value){
            return $this->getDefault($attribute, []);
        }

        $items = explode($this->getSeparator($attribute), $value);
        $items = array_map('trim', $items);
        $items = array_filter($items);
        return $items;
    }
}