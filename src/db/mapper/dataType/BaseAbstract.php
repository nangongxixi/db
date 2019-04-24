<?php

namespace j\db\mapper\dataType;

use j\db\mapper\DataTypeInterface;

/**
 * Class Integer
 * @package j\db\mapper\dateType
 */
abstract class BaseAbstract implements DataTypeInterface{

    public function getDefault($attrs, $default = null){
        return isset($attrs['default']) ? $attrs['default'] : $default;
    }

}