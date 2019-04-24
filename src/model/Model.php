<?php

namespace j\model;

use ArrayAccess;
use j\called\CalledTrait;
use j\db\Dao;
use j\error\Error;
use j\tool\ArrayUtils;

/**
 * Class Model
 * @package j\model
 *
 * 1. use $this->info visit row member
 * 2. use __get(key)/$this[$key] visit dynamic member
 */
class Model implements ArrayAccess, ValueMangerAwareInterface {

    use ValueMangerAwareTrait;
    use CalledTrait;

    protected $_id = 'id';
    protected $info = array();
    private $_isNew = false;

    /**
     * @var bool
     * is log changed key for update
     */
    protected $enableDirty = true;
    private $dirtyKeys = [];
    private $loadedProperty = [];

    /**
     * @var string
     * 存储时自动填充当前时间
     */
    protected $autoCreateDate;

    /**
     * @var bool
     */
    public $enableDao = true;

    /**
     * @var Dao
     */
    protected $dao;

    /**
     * Model constructor.
     * @param array $info
     * @param bool $isNew
     */
    public function __construct(array $info = [], $isNew = false) {
        $this->_isNew = $isNew;
        if($info){
            $this->exchange($info, $isNew);
        }
    }

    /**
     * @return bool
     */
    function isNew(){
        return $this->_isNew;
    }

    /**
     * implements
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value) {
        $this->__set($key, $value);
    }

    public function offsetUnset($offset) {
        unset($this->info[$offset]);
    }

    public function offsetExists($offset) {
        return isset($this->info[$offset]);
    }

    public function offsetGet($key) {
        return $this->__get($key);
    }

    function resetLoad($key){
        if(isset($this->loadedProperty[$key])){
            unset($this->info[$key]);
        }
    }

    /**
     * @param $key
     * @return mixed|null
     */
    function __get($key) {
        return $this->get($key);
    }

    function __set($key, $value) {
        $this->set($key, $value);
    }

    function __isset($key){
        return $this->offsetExists($key);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws
     */
    function __call($name, $arguments) {
        return $this->call($name, $arguments);
    }

    function __debugInfo(){
        return $this->info;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    function get($key){
        if(isset($this->info[$key]) || array_key_exists($key, $this->info)){
            return $this->info[$key];
        }

        $this->loadedProperty[$key] = true;
        return $this->info[$key] = $this->_load($key);
    }

    /**
     * todo lazy load
     * @param $key
     * @throws
     * @return mixed|null
     */
    protected function _load($key){
        if(strpos($key, 'is') === 0){
            $method = $key;
        } else {
            $method = 'get' . ucfirst($key);
        }

        if(method_exists($this, $method)){
            return $this->$method();
        }

        if($this->isCallable($method)){
            return $this->call($method);
        }

        return $this->loadValue($this, $key);
    }

    function set($key, $value){
        $this->info[$key] = $value;
        if($this->enableDirty && $key && !in_array($key, $this->dirtyKeys)){
            $this->dirtyKeys[] = $key;
        }
    }

    /**
     * @return mixed
     */
    public function identifier(){
        return $this->__get($this->_id);
    }

    /**
     * @param $value
     * @param bool $updateKey 是否设置标识符key
     */
    public function setIdentifier($value, $updateKey = false){
        if(!$updateKey){
            $this->__set($this->_id, $value);
        } else {
            $this->_id = $value;
        }
    }

    /**
     * set values
     *
     * @param array $info
     * @param boolean $isDirty
     */
    function exchange($info, $isDirty = true) {
        if($isDirty && $this->enableDirty){
            foreach($info as $key => $value){
                $this->set($key, $value);
            }
        } else {
            $this->info = array_merge($this->info, $info);
        }
    }

    /**
     * validate info
     *
     * @return boolean
     */
    public function validate() {
        /**
        $validator = new Validator($this->toStore());
        $validator
        ->rule('title', 'len[4,75]', '标题应该在4-75个字之间')
        ->requireFields(
            'title', 'content', 'cid'
            );
        return $validator->check($this->isNew());
        */
        return true;
    }

    /**
     * @param array $fields
     * @param array $extPicks 扩展字段, 如detail
     * @param bool $assoc 结果项强制为数组
     * @return array
     */
    function toArray($fields = [], $extPicks = [], $assoc = false) {
        if(!$fields && !$extPicks){
            return $this->info;
        }

        if($fields){
            $tmp = [];
            foreach($fields as $f){
                $tmp[$f] = $this->$f;
            }
        } else {
            $tmp = $this->info;
        }

        if($extPicks){
            foreach($extPicks as $f){
                if(!isset($tmp[$f])){
                    $tmp[$f] = $this->$f;
                }
            }
        }

        if($assoc){
            return ArrayUtils::toArray($tmp);
        }

        return $tmp;
    }
    /**
     * @return array
     */
    function toStore(){
        if($this->isNew() || !$this->enableDirty){
            if($this->isNew()
                && isset($this->autoCreateDate)
                && $this->autoCreateDate
            ){
                $this[$this->autoCreateDate] = date('Y-m-d H:i:s');
            }
            return $this->toArray();
        }

        $data = [];
        foreach($this->dirtyKeys as $key){
            $data[$key] = isset($this->info[$key]) ? $this->info[$key] : $this->__get($key);
        }

        return $data;
    }

    /**
     * @param Dao $dao
     */
    public function setDao($dao) {
        $this->dao = $dao;
    }

    /**
     * @return bool|int
     */
    public function save(){
        if(!isset($this->dao)){
            //trace('not found dao for model.save()');
            Error::warning('Dao invalid');
            return false;
        }

        if(!$this->isNew() && count($this->dirtyKeys) == 0) {
            Error::warning('Not data change');
            return false;
        }

        return $this->dao->save($this);
    }

    /**
     * @param bool $updateNewMode 是否更新 '新模型状态'
     */
    public function resetDataStatus($updateNewMode = false){
        $this->dirtyKeys = [];

        if($updateNewMode && $this->isNew()){
            $this->_isNew = false;
        }
    }
}
