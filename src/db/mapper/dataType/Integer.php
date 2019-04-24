<?php

namespace j\db\mapper\dataType;

/**
 * Class Integer
 * @package j\db\mapper\dateType
 */
class Integer extends BaseAbstract {

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function store($value, array $attribute){
        return $this->format($value, $attribute);
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function restore($value, array $attribute){
        return $this->format($value, $attribute);
    }


    protected function format($value, $attrs){
        if(!$value && 0 !== $value){
            $value = $this->getDefault($attrs, 0);
        }

        $value = intval($value);

        if(isset($attrs['max']) && $value > $attrs['max']){
            $value = $attrs['max'];
        } elseif(isset($attrs['min']) && $value < $attrs['min']){
            $value = $attrs['min'];
        }

        return $value;
    }
}