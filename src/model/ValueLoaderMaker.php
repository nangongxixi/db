<?php

namespace j\model;

use j\di\Container;

/**
 * Class ValueLoaderMaker
 * 值加载器工厂, 主要为了延迟ValueLoader到值加载时创建
 * @package j\model
 */
class ValueLoaderMaker {

    protected $args;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var ValueLoaderInterface
     */
    protected $loader;

    /**
     * ValueLoaderMaker constructor.
     * @param string $className
     * @param array $args
     * @param array $keys
     */
    public function __construct($className, $args = [], $keys = []){
        $this->className = $className;
        $this->args = $args;
    }

    /**
     * @return ValueLoaderInterface
     */
    function makeLoader(){
        if(isset($this->loader)){
            return $this->loader;
        }

        // $loader = className
        if(!class_exists($this->className)){
            throw new \RuntimeException("Invalid loader: {$this->className}");
        }

        $this->loader = Container::getInstance()->make($this->className, $this->args);
        return $this->loader;
    }
}