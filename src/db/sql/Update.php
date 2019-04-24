<?php

namespace j\db\sql;

use j\db\exception\SqlException;

/**
 * Database query builder for UPDATE statements.
 *
 * @package    j/Database
 * @category   Query
 */
class Update extends SqlWhere {

    // UPDATE ...
    protected $_table;

    // SET ...
    protected $_set = array();

    // (...)
    protected $_columns = array();

    /**
     * Set the values to update with an associative array.
     *
     * @param   array $pairs  associative (column => value) list
     * @return  Update
     */
    public function set(array $pairs){
        foreach ($pairs as $column => $value){
            $this->_set[] = array($column, $value);
        }
        return $this;
    }

    /**
     * Set the value of a single column.
     *
     * @param   mixed  $column name or array($table, $alias) or object
     * @param   mixed $value  column value
     * @return  Update
     */
    public function value($column, $value){
        $this->_set[] = array($column, $value);
        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @return  string
     * @throws SqlException
     */
    public function compile(){
        $set = $this->_compile_set($this->_set);
        if(!$set){
            throw new SqlException("Empty set for update");
        }

        if (empty($this->_where)){
            throw new SqlException("Not allow empty conditions for update");
        }

        // Start an update query
        $query = 'UPDATE ' . $this->quoter->quoteTable($this->_table);
        // Add the columns to update
        $query .= ' SET '. $set;

        $conditions = $this->_compile_conditions($this->_where);
        if(!$conditions){
            throw new SqlException("Not allow empty conditions for update");
        }

        // Add selection conditions
        $query .= ' WHERE ' .  $conditions;
        return $query;
    }

    /**
     * Set the columns that will be inserted.
     * @param array $columns
     * @return  $this
     */
    public function columns(array $columns){
        $this->_columns = $columns;
        return $this;
    }


    /**
     * Compiles an array of set values into an SQL partial. Used for UPDATE.
     *
     * @param   array $values   updated values
     * @return  string
     */
    protected function _compile_set(array $values){
        $set = array();

        foreach ($values as $group){
            // Split the set
            list ($column, $value) = $group;
            if($this->_columns && !in_array($column, $this->_columns)){
                continue;
            }
            // Quote the column name
            $column = $this->quoter->quoteIdentifier($column);

            if (is_string($value) AND array_key_exists($value, $this->_parameters)){
                // Use the parameter value
                $value = $this->_parameters[$value];
            }

            $set[$column] = $column . ' = ' . $this->quoter->quote($value);
        }
        return implode(', ', $set);
    }

    public function reset(){
        $this->_table = NULL;
        $this->_set   =
        $this->_where = array();
        $this->_parameters = array();
        return $this;
    }
} // End Update
