<?php

namespace j\db\profiler;

/**
 * Class Simple
 * @package j\db\profiler
 */
class Simple implements  InterfaceProfiler, DelayDisposeInterface{
    protected $sql = array();

    private static $instance = null;

    /**
     * @return Simple|null
     */
    static public function getInstance(){
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function profilerStart($target) {
        $this->sql[] = $target;
    }

    public function profilerFinish(){
    }

    public function getIteration(){
        return $this->sql;
    }

    public function flush($callback = null){
        $this->sql = [];
    }
}