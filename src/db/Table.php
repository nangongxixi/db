<?php

namespace j\db;

use j\db\exception\QueryException;
use j\di\Container;
use j\db\sql\Select;
use j\db\sql\SqlWhere;

/**
 * Class Table
 * @package j\db
 */
class Table{
    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $table = null;

    /**
     * @var string
     */
    protected $pk = 'id';

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     * field => op[=,!=, >, <, in, BETWEEN, not in, like]
     * field => condition[eg: < 15, > 20, like '%value%%']
     * key => array(field)
     * key => array(field, op)
     */
    protected $whereConf = array();

    /**
     * @var SqlFactory
     */
    protected $sql = null;

    /**
     * @var object
     */
    protected $asObject = null;

    /**
     * @var bool
     */
    protected $loadDefaultCond = false;

    /**
     * @param $name
     * @param null $adapter
     * @return \j\db\Table
     * @throws
     */
    static function factory($name, $adapter = null) {
        static $tables = array();
        if(isset($tables[$name])){
            return $tables[$name];
        }

        if(!$adapter){
            $service = Container::getInstance();
            $adapter = $service->get('dbAdapter');
        }
        $tables[$name] = new Table($name, $adapter);
        return $tables[$name];
    }

    /**
     * @param $table
     * @param Adapter $adapter
     */
    public function __construct($table, Adapter $adapter){
        $this->table = $table;
        $this->adapter = $adapter;
    }

    /**
     * @param Adapter $adapter
     */
    public function setAdapter($adapter) {
        $this->adapter = $adapter;
    }

    public function setWhereConf($key, $value = null){
        if(is_array($key)){
            $this->whereConf = $key;
        }else{
            $this->whereConf[$key] = $value;
        }
        return $this;
    }

    public function removeWhereConf($key){
        unset($this->whereConf[$key]);
    }

    public function getTableName(){
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->table;
    }

    public function setPrimkey($key){
        $this->pk = $key;
    }

    /**
     * @return string
     */
    public function getPk() {
        return $this->pk;
    }


    protected function getWhereConf(){
        if(!$this->loadDefaultCond){
            $this->loadDefaultCond = true;
            $columns = $this->getColumns();
            $defWhereConf = array();
            foreach ($columns as $c) {
                $defWhereConf[$c] = '=';
            }
            if($this->whereConf){
                $this->whereConf = array_merge($defWhereConf, $this->whereConf);
            }else{
                $this->whereConf = $defWhereConf;
            }
        }

        return $this->whereConf;
    }

    public function getColumns(){
        if(!$this->columns){
            $sql = 'SHOW COLUMNS FROM ' . $this->table;
            $columns = array();
            foreach ($this->adapter->query($sql, SqlFactory::SELECT) as $row)  {
                $columns[$row['Field']] = $row['Field'];
            }
            $this->columns = $columns;
        }
        return $this->columns;
    }

    public function resultAsObject($object = null){
        if($object == null){
            return $this->asObject;
        }
        return $this->asObject = $object;
    }

    public function returnArray($where){
        return isset($where['_array']) && $where['_array'];
    }

    /**
     * Initialize
     *
     * @throws Exception\RuntimeException
     */
    protected function initialize() {
        if($this->isInitialized) {
            return;
        }

        if(!$this->adapter instanceof Adapter) {
            throw new exception\RuntimeException('This table does not have an Adapter setup');
        }

        if(!is_string($this->table)) {
            throw new exception\RuntimeException('This table object does not have a valid table set.');
        }

        if(!$this->sql instanceof SqlFactory){
            $this->sql = new SqlFactory($this->table, $this->adapter);
        }

        $this->isInitialized = true;
    }

    /**
     * @return SqlFactory
     */
    function getSql() {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->sql;
    }

    /**
     * @param SqlWhere $query
     * @param $cond
     * @param array $whereConf
     * @return $this|Table
     */
    protected function where(SqlWhere $query, $cond, $whereConf = array()){
        if ($cond instanceof \Closure) {
            $cond($query);
            return $this;
        }

        if(empty($cond)){
            return $this;
        }

        $alias = $this->table;
        if(is_numeric($cond)){
            $query->where($alias . '.' . $this->pk, '=', $cond);
            return $this;
        }

        if(!is_array($cond)){
            return $this;
        }

        if(isset($cond['_limit'])){
            $query->limit($cond['_limit']);
            unset($cond['_limit']);
        }

        if(isset($cond['_callback'])){
            foreach((array)$cond['_callback'] as $call){
                $call($query, $cond);
            }
            unset($cond['_callback']);
        }

        if(is_numeric(key($cond))){
            $query->where($alias . '.' . $this->pk, 'IN', $cond);
            return $this;
        }

        return $this->whereCondition($query, $cond, $whereConf);
    }

    /**
     * todo 计算cond 与 whereConf的差集, 并警告
     * @param SqlWhere $query
     * @param $cond
     * @param array $whereConf
     * @return $this
     */
    protected function whereCondition(SqlWhere $query, $cond, $whereConf = array()){
        $whereConf || $whereConf = $this->getWhereConf();
        if(!$whereConf){
            return $this;
        }

        $alias = $this->table;
        $ops = array('=', '>', '>=', '<', '<=', '!=', 'BETWEEN', 'IN', 'NOT IN', 'LIKE');
        foreach ($whereConf as $field => $cnf) {
            if(!isset($cond[$field])){
                continue;
            }

            $value = $cond[$field];
            if ($cnf instanceof \Closure) {
                $cnf($query, $value, $cond);
                continue;
            }

            $op = '=';
            if(is_array($cnf)){
                $field = $cnf[0];
                if(2 === count($cnf)){
                    $op = $cnf[1];
                }
            }else{
                $op = $cnf;
            }

            $op = strtoupper($op);
            if($op == 'LIKE'){
                if(!$value){
                    continue;
                }
                $value = "%{$value}%";
            }elseif(!in_array($op, $ops)){
                $value = SqlFactory::expr(str_replace('%VALUE%', $value, $op));
                $op = '';
            }

            $query->where( $alias . '.' . $field, $op, $value);
        }

        return $this;
    }

    protected function whereSelect(Select $query, & $cond){
        if(!is_array($cond)){
            return $this;
        }

        if(isset($cond['_offset'])){
            $query->offset($cond['_offset']);
            unset($cond['_offset']);
        }

        if(isset($cond['_fields'])){
            if(is_array($cond['_fields'])){
                $query->columns($cond['_fields']);
            }elseif(is_numeric(strpos($cond['_fields'], ','))){
                $query->columns(explode(',', $cond['_fields']));
            }else{
                $query->columns($cond['_fields']);
            }
            unset($cond['_fields']);
        }

        if(isset($cond['_order']) && !isset($this->whereConf['_order'])){
            $order = $cond['_order'];
            if(is_array($order)){
                list($c, $d) = $order;
                $query->order_by($c, $d);
            } else {
                $query->order_by($order);
            }
            unset($cond['_order']);
        }

        return $this;
    }

    /**
     * @param $sql
     * @param $type
     * @return \j\db\driver\mysqli\ResultSet|bool
     */
    function execute($sql, $type = SqlFactory::UPDATE){
        return $this->adapter->query($sql, $type);
    }

    /**
     * @param SqlWhere|\Closure|string|array $where
     * @return bool
     * @throws \Exception
     */
    public function delete($where)  {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $delete = $this->sql->delete();
        if(!$where){
            throw new QueryException('Delete condition is null');
        }

        $this->where($delete, $where);

        if($delete->isEmptyWhere()){
            throw new QueryException("Query's where is empty for delete");
        }

        return $this->execute($delete);
    }

    /**
     * Select
     *
     * @param SqlWhere|\Closure|string|array $where
     * @param array $whereConf
     * @throws QueryException
     * @return driver\mysqli\ResultSet
     */
    public function find($where = null, $whereConf = array()) {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();
        if ($where !== null) {
            $this->where($select, $where, $whereConf);

            // select查询专用解析
            $this->whereSelect($select, $where);
        }

        if($select->isEmptyWhere()){
            throw new QueryException("Query's where is empty");
        }

        $rs = $this->execute($select, SqlFactory::SELECT);
        if(!$this->returnArray($where) && $this->asObject){
            $rs->asObject($this->asObject);
        }
        return $rs;
    }

    /**
     * @param $where
     * @return array|\j\model\Model|object|\stdClass
     * @throws QueryException
     */
    public function findOne($where){
        if(is_array($where)){
            $where['_limit'] = 1;
        }
        return $this->find($where)->current();
    }

    /**
     * @param $field
     * @param $cond
     * @return mixed
     */
    public function oneField($field, $cond){
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $cond['_fields'] = [$field];
        $cond['_limit'] = 1;

        $select = $this->sql->select();
        $this->where($select, $cond);
        $this->whereSelect($select, $cond);

        $rs = $this->execute($select, SqlFactory::SELECT)->current();
        return $rs ? $rs[$field] : null;
    }

    /**
     * Insert
     *
     * @param  array $set
     * @return int
     * @throws
     */
    public function insert($set) {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        $insert = $this->sql->insert();
        $insert->columns($this->getColumns());
        $insert->values($set);
        return $this->execute($insert, SqlFactory::INSERT);
    }

    /**
     * Update
     *
     * @param  array $set
     * @param  string|array|\closure $where
     * @throws
     * @return int
     */
    public function update($set, $where= null) {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $update = $this->sql->update();
        $update->columns($this->getColumns());
        $update->set($set);

        if ($where !== null) {
            $this->where($update, $where);
        }

        if($update->isEmptyWhere()){
            throw new QueryException("Query's where is empty for update");
        }

        return $this->execute($update, SqlFactory::UPDATE);
    }

    public function replace($set, $where){
        if($this->count($where)){
            $rs = $this->update($set, $where);
        } else {
            $rs = $this->insert($set);
        }
        return $rs;
    }

    public function count($where){
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();
        $this->where($select, $where);
        $select->columns(array(SqlFactory::expr('count(*) as total')), true);

        $select->reset(true);
        return $this->execute($select, SqlFactory::SELECT)->get('total');
    }

    public function sum($fields, $where){
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();
        $this->where($select, $where);
        $select->columns(array(SqlFactory::expr("sum(`{$fields}`) as total")), true);

        $select->reset(true);
        return $this->execute($select, SqlFactory::SELECT)->get('total');
    }

    public function avg($fields, $where){
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select();
        $this->where($select, $where);
        $select->columns(array(SqlFactory::expr("avg(`{$fields}`) as total")), true);

        $select->reset(true);
        return $this->execute($select, SqlFactory::SELECT)->get('total');
    }


    /**
     * @param callable $callback
     * @param int|array $start
     * @param int $step
     * @param null|callable $where
     * @return string
     */
    public function fetch(callable $callback, $start = 0, $step = 200, $where = null){
        $maxId = $this->getMaxId($where);
        if(is_array($start)){
            list($start, $stop) = $start;
            if($stop > $maxId){
                $stop = $maxId;
            }
        } else {
            $start = intval($start);
            $stop = $maxId;
        }

        $minId = $this->getMinId($where);
        if($start < $minId){
            $start = $minId;
        }


        $index = $start;
        $pos = $start;
        while ($pos <= $stop){
            $next = $pos + $step;
            $rs = $this->getRange($pos, $next, $where);
            foreach ($rs as $value) {
                call_user_func($callback, $value, ++$index); // body
            }
            $pos = $next;
        }

        return $stop;
    }

    protected function getRange($start, $end, callable $where = null){
        $select = $this->getSql()->select();
        $select->where($this->pk, '>=', $start);
        $select->where($this->pk, '<', $end);

        if(is_callable($where)){
            call_user_func($where, $select);
        }

        $rs = $this->execute($select, SqlFactory::SELECT);
        if($this->asObject){
            $rs->asObject($this->asObject);
        }
        return $rs;
    }

    public function getMaxId(callable $where = null, $field = null){
        if(!$field){
            $field = $this->pk;
        }
        $select = $this->getSql()->select();
        $select->columns(array(SqlFactory::expr("max({$field}) as max")));
        if(is_callable($where)){
            call_user_func($where, $select);
        }
        return $this->execute($select, SqlFactory::SELECT)->get('max');
    }

    public function getMinId(callable $where = null){
        $select = $this->getSql()->select();
        $select->columns(array(SqlFactory::expr("min({$this->pk}) as min")));
        if(is_callable($where)){
            call_user_func($where, $select);
        }
        return $this->execute($select, SqlFactory::SELECT)->get('min');
    }

    public function __toString(){
        return $this->getTableName() . '';
    }
}
