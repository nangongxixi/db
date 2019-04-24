<?php

namespace j\model;

use Closure;
use j\db\ResultSet;

/**
 * Class ValueManager
 * @package j\model
 * @property array $keyMap
 */
class ValueManager
    implements ValueLoaderInterface
{

    /**
     * @var ValueLoaderInterface[]
     */
    public $loaders = [];

    /**
     * @var callable
     */
    protected $initializer;

    /**
     * @var bool
     */
    protected $isInit = false;

    /**
     * ValueManager constructor.
     * @param ValueLoaderInterface[] $loaders
     * @param callable $initializer defer value loaders
     */
    public function __construct(array $loaders = [], callable $initializer = null){
        if($loaders){
            $this->addLoader($loaders);
        }
        $this->initializer = $initializer;
    }

    /**
     * lazy build keys
     * @param $name
     * @return mixed
     */
    public function __get($name){
        if($name == 'keyMap'){
            $this->keyMap = $this->genKeyMap();
            return $this->keyMap;
        }

        return null;
    }

    protected function genKeyMap(){
        $hasMap = [];
        foreach($this->loaders as $hash => $loader){
            foreach($loader->getKeys() as $key){
                $hasMap[$key] = $hash;
            }
        }
        return $hasMap;
    }

    /**
     * @param ValueLoaderInterface|Closure|array $loader
     * @param array $keys
     * @return $this
     */
    function addLoader($loader, $keys = []){
        if(is_array($loader)){
            // [[Closure, key], ValueLoaderInterface, ...]
            foreach($loader as $_l){
                if(is_array($_l)){
                    $this->addLoader(new ClosureValueLoader($_l[1], $_l[0]));
                } else {
                    $this->addLoader($_l);
                }
            }
        } else {
            if(($loader instanceof Closure) && $keys){
                $loader = new ClosureValueLoader($keys, $loader);
            }

            $hash = spl_object_hash($loader);
            if(isset($this->loaders[$hash])){
                return $this;
            }

            $this->loaders[$hash] = $loader;

            if(isset($this->keyMap)){
                foreach($loader->getKeys() as $key){
                    $this->keyMap[$key] = $hash;
                }
            }
        }
        return $this;
    }

    /**
     * @param string $key
     * @param Model|array $context
     * @return mixed
     */
    function loadValue($context, $key) {
        if(!$this->isInit && isset($this->initializer)){
            $this->isInit = true;
            call_user_func($this->initializer, $this);
        }

        if(!$this->isCanLoad($context, $key)){
            return null;
        }

        $hash = $this->keyMap[$key];
        return $this->loaders[$hash]->loadValue($context, $key);
    }

    /**
     * @return array
     */
    function getKeys(){
        return array_keys($this->keyMap);
    }

    /**
     * @param $key
     * @param Model|array $context
     * @return bool
     */
    function isCanLoad($context, $key) {
        return $this->keyMap && isset($this->keyMap[$key]);
    }

    /**
     * 装饰结果集
     * @param ResultSet $rs
     */
    public function decorateList($rs){
        $rs->setExtend(function($row) {
            if($row instanceof ValueMangerAwareInterface){
                $row->setValueManger($this);
            }
        });
    }
}

