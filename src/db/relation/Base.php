<?php

namespace j\db\relation;

use j\db\Dao;
use j\db\Table;
use Exception;
use j\di\Container;
use j\model\Model;

/**
 * Class Base
 * @package j\db\relation
 * @property string $key
 */
abstract class Base {
    /**
     * @var Dao|Table
     */
    protected $dao;

    /**
     * @param Model $model
     * @param [] $options
     * @return mixed
     */
    abstract function load($model, $options = []);

    /**
     * @var Dao
     */
    protected $context;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $keyMap = [];


    /**
     * HasOne constructor.
     * @param Dao $context
     * @param $config
     * @throws Exception
     */
    public function __construct($context, $config){
        if(!isset($config['keys'])){
            throw new Exception('Invalid config(keys is null)');
        } else {
            if(count($config['keys']) != 2){
                throw new Exception('Invalid keys(eg: [key1, key2])');
            }
            $this->keyMap = $config['keys'];
        }

        $this->context = $context;
        $this->options = $config;
    }

    /**
     * @return Dao|Table
     * @throws Exception
     */
    function getDao(){
        if(isset($this->dao)){
            return $this->dao;
        }

        $config = $this->options;

        if(isset($config['table'])){
            $table = $config['table'];
            if(is_string($table)){
                #$table = $this->context->getService('dbTable', $table);
                $table = Container::getInstance()->get('dbTable', $table);
                if(isset($config['pk'])){
                    $table->setPrimkey($config['pk']);
                }
                if(isset($config['define'])) {
                    call_user_func($config['define'], $table);
                }
            } elseif(!is_object($table)){
                throw new Exception('Invalid table');
            }
            $this->dao = $table;
        } elseif(isset($config['model'])) {
            $this->dao = $config['model'];
            if(is_string($this->dao)){
                #$this->dao = $this->context->getService('dao', $config['model']);
                $this->dao = Container::getInstance()->get('dao', $config['model']);
            }
        } else {
            throw new Exception('Invalid config');
        }

        return $this->dao;
    }

    /**
     * @var
     */
    protected $key;

    /**
     * @param Model $model
     * @param array $options
     * @return mixed
     */
    protected function buildCond($model, $options = []){
        // target cond
        if(isset($this->options['cond'])){
            $options = array_merge($this->options['cond'], $options);
            if(isset($this->options['parseCond'])
                && $this->options['parseCond']){
                foreach($options as $key => $value){
                    if(preg_match('/\{(.*)\}/', $value, $r)){
                        $options[$key] = $model[$r[1]];
                    }
                }
            }
        }

        // target key
        $options[$this->keyMap[0]] = $model[$this->keyMap[1]];
        return $options;
    }
}
