<?php

namespace j\db\sql;

/**
 * Class SqlWhere
 * @package j\db\sql
 */
abstract class SqlWhere extends SqlAbstract {

    protected $_table;

    // WHERE ...
    protected $_where = array();

    // ORDER BY ...
    protected $_order_by = array();

    // LIMIT ...
    protected $_limit = NULL;


    /**
     * Set the table and columns for an insert.
     *
     * @param   mixed $table table name or array($table, $alias) or object
     */
    public function __construct($table = null){
        if($table)
            // Set the inital table name
            $this->setTable($table);
    }

    /**
     * Sets the table to delete from.
     *
     * @param   mixed $table  table name or array($table, $alias) or object
     * @return  $this
     */
    public function setTable($table){
        $this->_table = $table;
        return $this;
    }

    /**
     * Alias of and_where()
     *
     * @param   mixed $column  column name or array($column, $alias) or object
     * @param   string $op  logic operator
     * @param   mixed  $value column value
     * @return  $this
     */
    public function where($column, $op = null, $value = null){
        return $this->and_where($column, $op, $value);
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed $column  column name or array($column, $alias) or object
     * @param   string $op logic operator
     * @param   mixed $value  column value
     * @return  $this
     */
    public function and_where($column, $op = null, $value = null){
        if ($column instanceof \Closure) {
            $column($this);
            return $this;
        }

        $this->_where[] = array('AND' => array($column, $op, $value));
        return $this;
    }

    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param   mixed $column  column name or array($column, $alias) or object
     * @param   string $op  logic operator
     * @param   mixed $value  column value
     * @return  $this
     */
    public function or_where($column, $op, $value){
        $this->_where[] = array('OR' => array($column, $op, $value));

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function where_open(){
        return $this->and_where_open();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_open(){
        $this->_where[] = array('AND' => '(');

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_open(){
        $this->_where[] = array('OR' => '(');

        return $this;
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function where_close(){
        return $this->and_where_close();
    }

    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_close(){
        $this->_where[] = array('AND' => ')');

        return $this;
    }

    /**
     * Closes an open "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_close(){
        $this->_where[] = array('OR' => ')');

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed $column  column name or array($column, $alias) or object
     * @param   string $direction direction of sorting
     * @return  $this
     */
    public function order_by($column, $direction = NULL){
        $this->_order_by[] = array($column, $direction);

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param   integer $number  maximum results to return
     * @return  $this
     */
    public function limit($number){
        $this->_limit = (int) $number;

        return $this;
    }

    function isEmptyWhere(){
        return empty($this->_where) && empty($this->_limit);
    }

} // End Where
