<?php

namespace j\model;

/**
 * Trait TraitValueManager
 * @package common
 */
trait ValueMangerAwareTrait {

    /**
     * @var ValueManager
     */
    protected $__valueManger;

    /**
     * @param ValueManager $manger
     */
    function setValueManger($manger){
        if(isset($this->__valueManger)
            && spl_object_hash($manger) != spl_object_hash($this->__valueManger)){
            $this->__valueManger->addLoader($manger->getKeys(), $manger);
        } else {
            $this->__valueManger = $manger;
        }
    }

    protected function loadValue($context, $key) {
        if(isset($this->__valueManger)){
            return $this->__valueManger->loadValue($context, $key);
        } else {
            return null;
        }
    }

    protected function hasValueManager(){
        return isset($this->__valueManger);
    }

    public function __sleep(){
        $vars = array_keys(get_object_vars($this));
        unset($vars[array_search('__valueManger', $vars)]);
        return $vars;
    }
}