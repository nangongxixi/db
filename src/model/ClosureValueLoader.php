<?php

namespace j\model;

/**
 * Class ClosureValueLoader
 * @package j\model
 */
class ClosureValueLoader extends ValueLoaderAbstract {

    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * ClosureValueLoader constructor.
     * @param array|string $key
     * @param \Closure $callback
     */
    public function __construct($key, \Closure $callback){
        parent::__construct($key);
        $this->callback = $callback;
    }

    protected function getFiledValue($context, $key){
        $method = $this->callback;
        return $method($context, $key);
    }
}