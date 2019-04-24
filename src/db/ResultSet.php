<?php

namespace j\db;

use Closure;
use j\model\Model;

/**
 * Class ResultSet
 * @package j\db
 */
abstract class ResultSet implements
    \Countable,
    \Iterator,
    \SeekableIterator,
    \ArrayAccess
{
    // Executed SQL for this result
    protected $query;

    // Raw result resource
    protected $result;

    // Total number of rows and current row
    protected $total_rows  = 0;
    protected $current_row = 0;
    protected $internal_row = 0;

    // Return rows as an object or associative array
    protected $asObject;

    /**
     * @var tmp row object for clone
     */
    protected $rowObject;


    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected $extenders = [];

    /**
     * Get the extender callbacks for a given type.
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getExtenders($abstract = 'model'){
        return $this->extenders;
    }

    /**
     * "Extend" an abstract type in the container.
     * @param \Closure|array|SetDecoratorInterface  $extend
     * @throws \InvalidArgumentException
     */
    public function setExtend($extend)
    {
        if($extend instanceof Closure){
            $this->extenders[] = $extend;
        } elseif($extend instanceof SetDecoratorInterface){
            $extend->decorate($this);
        } elseif(is_array($extend)){
            foreach($extend as $_extend){
                $this->setExtend($_extend);
            }
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @param $object
     * @return mixed
     */
    protected function extend($object){
        // extend
        foreach ($this->getExtenders() as $extender) {
            if($extend = $extender($object)){
                $object = $extend;
            }
        }
        return $object;
    }


    /**
     * @param $result
     * @param $sql
     * @param null $as_object
     */
    public function __construct($result, $sql, $as_object = null) {
        // Store the result locally
        $this->result = $result;

        // Store the SQL locally
        $this->query = $sql;
        $this->asObject($as_object);

        // Find the number of rows in the result
        $this->init();
    }

    protected function init(){
    }

    public function __destruct(){
        $this->close();
    }

    public function close(){
    }

    function asObject($as_object)
    {
        if (is_object($as_object))  {
            // Get the object class name
            $as_object = get_class($as_object);
        }

        // Results as objects or associative arrays
        $this->asObject = $as_object;
        return $this;
    }

    /**
     * put your comment there...
     *
     * @param mixed $name
     * @param mixed $default
     * @return string
     */
    public function get($name, $default = NULL)
    {
        $row = $this->current();
        if ($this->asObject){
            if(method_exists($row, '__get'))
                return $row->$name;
            if (isset($row->$name))
                return $row->$name;
        }else {
            if (isset($row[$name]))
                return $row[$name];
        }

        return $default;
    }


    /**
     * put your comment there...
     *
     */
    public function count()
    {
        return $this->total_rows;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ($offset >= 0 AND $offset < $this->total_rows);
    }

    public function offsetGet($offset)
    {
        if(!is_numeric($offset)){
            return null;
        }

        if (!$this->seek($offset))
            return NULL;

        return $this->current();
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws
     */
    final public function offsetSet($offset, $value)  {
        throw new \Exception('Database results are read-only');
    }

    /**
     * @param mixed $offset
     * @throws
     */
    final public function offsetUnset($offset)  {
        throw new \Exception('Database results are read-only');
    }

    /**
     * put your comment there...
     *
     */
    public function key()  {
        return $this->current_row;
    }

    public function next()  {
        ++$this->current_row;
        return $this;
    }

    public function prev()  {
        --$this->current_row;
        return $this;
    }

    public function rewind()  {
        $this->current_row = 0;
        return $this;
    }

    /**
     * @param int $position
     * @return boolean
     */
    abstract function seek($position);

    /**
     * @return bool
     */
    public function valid() {
        return $this->offsetExists($this->current_row);
    }

    /**
     * Return all of the rows in the result as an array.
     *
     *     // Indexed array of all rows
     *     $rows = $result->as_array();
     *
     *     // Associative array of rows by "id"
     *     $rows = $result->as_array('id');
     *
     *     // Associative array of rows, "id" => "name"
     *     $rows = $result->as_array('id', 'name');
     *
     * @param   string  $key for associative keys
     * @param   string  $value for values
     * @param   boolean  $unset for values
     * @param   boolean  $fromAssocIterator
     * @return  array
     */
    public function toArray(
        $key = NULL, $value = NULL,
        $unset = false, $fromAssocIterator = false)
    {
        $results = array();

        if($fromAssocIterator){
            $list = $this->getAssocIterator();
        } else {
            $list = $this;
        }

        if ($key === NULL AND $value === NULL){
            // Indexed rows

            foreach ($list as $row) {
                $results[] = $row;
            }
        }elseif ($key === NULL) {
            // Indexed columns

            if (!$fromAssocIterator && $this->asObject) {
                foreach ($list as $row) {
                    $results[] = $row->$value;
                }
            }  else {
                foreach ($list as $row){
                    $results[] = $row[$value];
                }
            }
        }elseif ($value === NULL) {
            // Associative rows

            if (!$fromAssocIterator && $this->asObject)  {
                foreach ($list as $row) {
                    $results[$row->$key] = $row;
                }
            }else {
                foreach ($list as $row){
                    $id = $row[$key];
                    if($unset){
                        unset($row[$key]);
                    }
                    $results[$id] = $row;
                }
            }
        } else  {
            // Associative columns

            if (!$fromAssocIterator && $this->asObject) {
                foreach ($list as $row)  {
                    $results[$row->$key] = $row->$value;
                }
            }  else {
                foreach ($list as $row) {
                    $results[$row[$key]] = $row[$value];
                }
            }
        }
        $this->rewind();
        return $results;
    }

    /**
     * @return array|\Iterator
     */
    function getAssocIterator(){
        return is_array($this->result) ? $this->result : $this;
    }

    /**
     * @var mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * @param mapper\DataMapper $dataMapper
     */
    public function setDataMapper($dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function current()
    {
        $row = $this->fetchRow();
        if(!$row){
            return $row;
        }

        if(isset($this->dataMapper)){
            $row = $this->dataMapper->restore($row);
        }

        if(isset($this->asObject)){
            if($this->asObject === true)
            {
                $row = (object)$row;
            }
            else if(is_string($this->asObject))
            {
                if(!isset($this->rowObject)){
                    $this->rowObject = new $this->asObject;
                }

                $obj = clone $this->rowObject;
                if($this->rowObject instanceof Model){
                    /** @var Model $obj */
                    $obj->exchange($row, false);
                } else {
                    foreach($row as $key => $value){
                        $obj->$key = $value;
                    }
                }
                $row = $obj;
            }
        }

        if(isset($this->extenders)){
            return $this->extend($row);
        } else {
            return $row;
        }
    }

    /**
     * @return array
     */
    abstract protected function fetchRow();
} // End JDR
