<?php

namespace j\db\sql;
use j\db\exception\SqlException;
use j\db\sql\SqlWhere;
use Exception;

/**
 * Database query builder for DELETE statements.
 *
 * @package    j/Database
 * @category   Query
 */
class Delete extends SqlWhere {

    /**
     * @return string
     * @throws Exception
     */
    public function compile(){
        // Start a deletion query
        $query = 'DELETE FROM ' . $this->quoter->quoteTable($this->_table);

        if (empty($this->_where)){
            throw new SqlException("Not allow empty conditions for delete");
        }

        $conditions = $this->_compile_conditions($this->_where);
        if(!$conditions){
            throw new SqlException("Not allow empty conditions for delete");
        }

        // Add deletion conditions
        $query .= ' WHERE '. $conditions;
        if ( ! empty($this->_order_by)){
            // Add sorting
            $query .= ' '.$this->_compile_order_by($this->_order_by);
        }

        if ($this->_limit !== NULL){
            // Add limiting
            $query .= ' LIMIT '.$this->_limit;
        }

        return $query;
    }

    public function reset(){
        $this->_table = NULL;
        $this->_where = array();
        $this->_parameters = array();
        return $this;
    }

} // End Delete
