<?php

namespace j\mongo;

use j\model\Model;
use Iterator;
use Countable;
use MongoCursor;

/**
 * Class QuerySet
 * @package j\mongo
 */
class QuerySet implements Iterator, Countable {
    /**
     * @var \MongoCursor
     */
    private $cursor;
    private $bindClass;

    public function __construct(MongoCursor $iterator, $bindClass){
        $this->cursor = $iterator;
        $this->bindClass = $bindClass;
    }

    /**
     * @param mixed $bindClass
     */
    public function setBindClass($bindClass){
        $this->bindClass = $bindClass;
    }

    /**
     * @return array|Model
     */
    public function current( ){
        $info = $this->cursor->current();
        if(!$info){
            return [];
        }

        return $this->asObject($info);
    }

    /**
     * @param $info
     * @return Model
     */
    protected function asObject($info) {
        if($this->bindClass){
            /** @var Model $module */
            $module = new $this->bindClass;
            $module->exchange($info);
            return $module;
        } else{
            return $info;
        }
    }

    function rewind() {
        $this->cursor->rewind();
    }

    function key() {
        return $this->cursor->key();
    }

    function next() {
        $this->cursor->next();
    }

    function valid() {
        return $this->cursor->valid();
    }

    function count(){
        return $this->cursor->count();
    }

    function getNext(){
        $info = $this->cursor->getNext();
        if(!$info){
            return [];
        }

        return $this->asObject($info);
    }

    /**
     * put your comment there...
     *
     * @param mixed $name
     * @param mixed $args
     * @return mixed
     */
    function __call($name, $args){
//        $r = new ReflectionClass($this->iterator);
//        if($method = $r->getMethod($name)){
//            if($method->isPublic() && !$method->isAbstract()){
//                return $method->invoke($this->iterator, $args);
//            }
//        }
//        throw(new Exception('Invalid call'));
        return call_user_func_array(array($this->cursor, $name), $args);
    }
}