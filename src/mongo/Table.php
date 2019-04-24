<?php

namespace j\mongo;

use j\model\Model;
use Exception;
use MongoCollection;
use MongoClient;
use MongoId;
use MongoDB;

/**
 * Class Table
 * @package j\mongo
 */
class Table {
    protected $dbName;
    protected $bindClass;

    /**
     * @var string
     */
    private static $database = 'aom';

    /**
     * @var  MongoClient
     */
    private static $conn;

    /**
     * @var  MongoCollection
     */
    protected $db;

    /**
     * @param string $tableName
     * @param null|string|Object $bind
     * @param null|MongoCollection $db
     */
    function __construct($tableName, $bind = '', $db = null){
        $this->setTableName($tableName);
        $this->setBindClass($bind);
        $this->init();
        $this->setDb($db);
    }

    /**
     * @param string $dbName
     */
    function setTableName($dbName){
        if($dbName)
            $this->dbName = $dbName;
    }

    protected function init(){
        if(!isset($this->dbName) || !$this->dbName){
            preg_match('/[a-zA-Z]+$/', get_class($this), $r);
            $tableName = $r[0];
            $this->setTableName(strtolower($tableName));
        }
    }

    /**
     * @param null|Object|string $bindClass
     */
    public function setBindClass($bindClass){
        if($bindClass || is_null($bindClass))
            $this->bindClass = $bindClass;
    }

    /**
     * @param \MongoCollection $db
     */
    public function setDb($db){
        if(!$db){
            if(!isset($this->db)){
                $this->db = self::mongo()
                    ->selectDB(self::$database)
                    ->selectCollection($this->dbName);
            }
        }else{
            $this->db = $db;
        }
    }

    /**
     * put your comment there...
     * @return MongoClient
     */
    protected function mongo(){
        if(!isset(static::$conn)){
            static::$conn = new MongoClient();
        }
        return static::$conn;
    }

    public $lastInsertResult = [];

    /**
     * @param Model $mod
     * @return bool
     */
    function insert(Model $mod){
        if(!$mod->validate()){
            return false;
        }

        $info = $mod->toArray();

        $this->lastInsertResult = $this->db->insert($info);
        $mod->identifier($info['_id']);

        if(!$this->lastInsertResult['err']){
            return true;
        }

        return false;
    }

    public $lastRemoveResult = [];

    /**
     * @param Model $mod
     * @return bool
     */
    function remove(Model $mod){
        $cond = array(
            '_id' => new MongoId($mod->identifier() . '')
        );
        $this->lastRemoveResult = $this->db->remove($cond);
        return $this->parseResult($this->lastRemoveResult);
    }

    public $lastUpdateResult = [];

    /**
     * @param $return
     * @return bool
     */
    protected function parseResult($return){
        if(!$return['err']){
            return true;
        }
        return false;
    }

    /**
     * @param Model $mod
     * @return bool
     */
    function update(Model $mod){
        $info = $mod->toArray();
        unset($info['_id']);
        $id = $mod->identifier();
        $this->lastUpdateResult = $this->db->update(
            ['_id' => $id],
            ['$set' => $info]
            );
        return $this->parseResult($this->lastUpdateResult);
    }

    /**
     * @param $id
     * @param $set
     * @return bool
     */
    function updateSet($id, $set) {
        $this->lastUpdateResult = $this->db->update(
            ['_id' => new MongoId($id . '')],
            ['$set' => $set]
            );
        return $this->parseResult($this->lastUpdateResult);
    }

    /**
     * @param string $id
     * @return array|Model|null
     */
    function findOne($id){
        $cond = array(
            '_id' => new MongoId($id . '')
        );
        $info = $this->db->findOne($cond);
        return $this->bindClass($info);
    }

    /**
     * @param $info
     * @return Model
     */
    protected function bindClass($info){
        if(!$info){
            return null;
        }

        if($this->bindClass){
            $className = is_object($this->bindClass) ? get_class($this->bindClass) : $this->bindClass;
            /** @var Model $model */
            $model = new $className;
            $model->exchange($info);
        }else{
            $model = $info;
        }
        return $model;
    }

    /**
     * @param array $cond
     * @param array $options
     * @return QuerySet|\MongoCursor
     */
    function find($cond = array(), $options = []){
        // total
        $cursor = $this->db->find($cond);
        if(isset($options['start'])){
            $cursor->skip($options['start']);
        }

        if(isset($options['offset'])){
            $cursor->limit($options['offset']);
        }elseif(isset($options['limit'])){
            $cursor->limit($options['limit']);
        }

        if($this->bindClass) {
            return new QuerySet($cursor, $this->bindClass);
        }else{
            return $cursor;
        }
    }

    function count($cond = []){
        return $this->db->count($cond);
    }
}
