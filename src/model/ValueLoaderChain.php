<?php

namespace j\model;

/**
 * Class ValueLoaderChain
 * @package j\model
 */
class ValueLoaderChain extends ValueLoaderAbstract {
    /**
     * @var ValueLoaderInterface[]
     */
    protected $chain = [];

    /**
     * LinkDao constructor.
     * @param string $fieldName
     * @param ValueLoaderInterface[] $chain
     */
    public function __construct($chain, $fieldName){
        parent::__construct($fieldName);
        $this->chain = $chain;
    }

    /**
     * @param $context
     * @param $key
     * @return mixed|null
     */
    protected function getFiledValue($context, $key){
        foreach($this->chain as $loader){
            $value = $loader->loadValue($context, $key);
            if(!is_null($value)){
                return $value;
            }
        }
        return null;
    }
}