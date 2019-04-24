<?php

namespace j\db\mapper\dataType;

/**
 * Class DataTime
 * @package j\db\mapper\dateType
 */
class DateTime extends BaseAbstract {

    const FORMAT = 'Y-m-d H:i:s';

    /**
     * @param $attrs
     * @param $default
     * @return false|null|string
     */
    public function getDefault($attrs, $default = null){
        if(isset($attrs['autoCreate'])){
            return date($this->getFormat($attrs));
        }
        return parent::getDefault($attrs, $default);
    }

    protected function isInt($attrs){
        return isset($attrs['int']) && $attrs['int'];
    }

    protected function getFormat($attrs){
        return gav($attrs, 'format', self::FORMAT);
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function store($value, array $attribute){
        if(!$value){
            $value = $this->getDefault($attribute);
        }

        if(is_string($value) && $this->isInt($attribute)){
            return strtotime($value);
        } else {
            return $value;
        }
    }

    /**
     * @param $value
     * @param array $attribute
     * @return mixed
     */
    public function restore($value, array $attribute){
        if(!$value){
            return $this->getDefault($attribute);
        }
        if(!is_numeric($value)){
            $value = strtotime($value);
        }
        return date($this->getFormat($attribute), $value);
    }
}
