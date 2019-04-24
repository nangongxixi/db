<?php
namespace j\db\sql;

/**
 * Database query builder for SELECT statements.
 *
 * @package    j/Database
 * @category   Query
 */
class Select extends SqlWhere {

    // SELECT ...
    protected $_select = array();

    // DISTINCT
    protected $_distinct = FALSE;

    // FROM ...
    protected $_table = array();

    // JOIN ...
    protected $_join = array();

    // GROUP BY ...
    protected $_group_by = array();

    // HAVING ...
    protected $_having = array();

    // OFFSET ...
    protected $_offset = NULL;

    // The last JOIN statement created
    protected $_last_join;

    /**
     * @var bool
     */
    protected $compiled = false;

       /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param   boolean $value  enable or disable distinct columns
     * @return  Select
     */
    public function distinct($value){
        $this->_distinct = (bool) $value;
        return $this;
    }

    /**
     * Choose the columns to select from, using an array.
     *
     * @param   array $columns list of column names or aliases
     * @return  Select
     */
    public function columns(array $columns, $merge = false){
        if(!$merge){
            $this->_select = array_merge($this->_select, $columns);
        }else{
            $this->_select = $columns;
        }

        return $this;
    }

    /**
     * Choose the tables to select "FROM ..."
     *
     * @param   mixed $tables  table name or array($table, $alias) or object
     * @param   ...
     * @return  Select
     */
    public function from($tables){
        $tables = func_get_args();
        $this->_table = array_merge($this->_table, $tables);
        return $this;
    }

    /**
     * Choose the tables to select "FROM ..."
     *
     * @param   mixed  $tables table name or array($table, $alias) or object
     * @param   ...
     * @return  Select
     */
    public function setTable($tables){
        $tables = func_get_args();
        $this->_table = array_merge($this->_table, $tables);
        return $this;
    }

    /**
     * Adds addition tables to "JOIN ...".
     *
     * @param   mixed $table  column name or array($column, $alias) or object
     * @param   string  $type join type (LEFT, RIGHT, INNER, etc)
     * @return  Select
     */
    public function join($table, $type = NULL){
        $this->_last_join = new Join($table, $type);
        $this->_last_join->setQuoter($this->quoter);
        $this->_join[] = $this->_last_join;
        return $this;
    }

    /**
     * Adds "ON ..." conditions for the last created JOIN statement.
     *
     * @param   mixed $c1  column name or array($column, $alias) or object
     * @param   string $op  logic operator
     * @param   mixed $c2  column name or array($column, $alias) or object
     * @return  Select
     */
    public function on($c1, $op, $c2){
        $this->_last_join->on($c1, $op, $c2);

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param   mixed  $columns  column name or array($column, $alias) or object
     * @param   ...
     * @return  Select
     */
    public function group_by($columns){
        $columns = func_get_args();
        $this->_group_by = array_merge($this->_group_by, $columns);
        return $this;
    }

    /**
     * Alias of and_having()
     *
     * @param   mixed  $column column name or array($column, $alias) or object
     * @param   string $op logic operator
     * @param   mixed $value  column value
     * @return  Select
     */
    public function having($column, $op, $value = NULL){
        return $this->and_having($column, $op, $value);
    }

    /**
     * Creates a new "AND HAVING" condition for the query.
     *
     * @param   mixed $column   column name or array($column, $alias) or object
     * @param   string $op  logic operator
     * @param   mixed  $value  column value
     * @return  Select
     */
    public function and_having($column, $op, $value = NULL){
        $this->_having[] = array('AND' => array($column, $op, $value));

        return $this;
    }

    /**
     * @param $column
     * @param $op
     * @param null $value
     * @return $this
     */
    public function or_having($column, $op, $value = NULL){
        $this->_having[] = array('OR' => array($column, $op, $value));

        return $this;
    }

    /**
     * Alias of and_having_open()
     *
     * @return  Select
     */
    public function having_open(){
        return $this->and_having_open();
    }

    /**
     * Opens a new "AND HAVING (...)" grouping.
     *
     * @return  Select
     */
    public function and_having_open(){
        $this->_having[] = array('AND' => '(');

        return $this;
    }

    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return  Select
     */
    public function or_having_open(){
        $this->_having[] = array('OR' => '(');

        return $this;
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  Select
     */
    public function having_close(){
        return $this->and_having_close();
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  Select
     */
    public function and_having_close(){
        $this->_having[] = array('AND' => ')');

        return $this;
    }

    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return  Select
     */
    public function or_having_close(){
        $this->_having[] = array('OR' => ')');

        return $this;
    }

    /**
     * @param $number
     * @return $this
     */
    public function offset($number){
        $this->_offset = (int) $number;
        return $this;
    }

    /**
     * @param bool $forCount
     * @return string
     */
    public function compile($forCount = false){
        // Callback to quote identifiers
        $quote_ident = array($this->quoter, 'quoteIdentifier');

        // Callback to quote tables
        $quote_table = array($this->quoter, 'quoteTable');

        // Start a selection query
        $column = $table = $join = $where = '';
        $query = 'SELECT ';

        if ($this->_distinct === TRUE){
            // Select only unique results
            $query .= 'DISTINCT ';
        }

        if (empty($this->_select)){
            // Select all columns
            $query .= '*';
        }else{
            // Select all columns
            $query .= implode(', ', array_unique(array_map($quote_ident, $this->_select)));
        }

        if ( ! empty($this->_table)){
            // Set tables to select from
            $table = ' FROM '.implode(', ', array_unique(array_map($quote_table, $this->_table)));
            $query .= $table;
        }

        if ( ! empty($this->_join)){
            // Add tables to join
            $join = ' ' . $this->_compile_join($this->_join);
            $query .= $join;
        }

        if ( ! empty($this->_where)){
            // Add selection conditions
            $where = ' WHERE ' . $this->_compile_conditions($this->_where);
            $query .= $where;
        }

        if ( ! empty($this->_group_by)){
            // Add sorting
            $groups = implode(', ', array_map($quote_ident, $this->_group_by));
            $query .= ' GROUP BY ' . $groups;
        }

        if ( ! empty($this->_having)){
            // Add filtering conditions
            $query .= ' HAVING '. $this->_compile_conditions($this->_having);
        }

        if ( ! empty($this->_order_by)){
            // Add sorting
            $query .= ' '. $this->_compile_order_by($this->_order_by);
        }

        if ($this->_limit !== NULL){
            // Add limiting
            $query .= ' LIMIT '.$this->_limit;
        }

        if ($this->_offset !== NULL){
            // Add offsets
            $query .= ' OFFSET '.$this->_offset;
        }

        return $query;
    }

    public function reset($forCount = false){
        $this->_group_by =
        $this->_having   =
        $this->_order_by = array();
        $this->_limit     = NULL;
        $this->_offset    = NULL;
        $this->_parameters =array();

        if(!$forCount){
            $this->_select   =
            $this->_table     =
            $this->_join     =
            $this->_where    =
            $this->_distinct = FALSE;
            $this->_last_join = NULL;
            $this->compiled = false;
        }

        return $this;
    }

} // End Select

