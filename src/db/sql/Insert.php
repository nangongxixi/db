<?php

namespace j\db\sql;

use j\db\exception\SqlException;

/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-6-23
 * Time: 下午3:23
 * To change this template use File | Settings | File Templates.
 */
class Insert extends SqlWhere {
    // (...)
    protected $_columns = array();

    // VALUES (...)
    protected $_values = array();

    /**
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns){
        $this->_columns = $columns;
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     * @throws SqlException
     */
    public function values(array $values){
        if ( ! is_array($this->_values)){
            throw new SqlException('INSERT INTO ... SELECT statements cannot be combined with INSERT INTO ... VALUES');
        }

        // Get all of the passed values
        $values = func_get_args();
        $this->_values = array_merge($this->_values, $values);
        return $this;
    }

    /**
     * Use a sub-query to for the inserted values.
     *
     * @param   object  Select type
     * @return  Insert
     */
    public function select(Select $query){
        $this->_values = $query;
        return $this;
    }

    /**
     * @return string
     * @throws SqlException
     */
    public function compile(){
        // Start an insertion query
        $table = $this->quoter->quoteTable($this->_table);
        $query = 'INSERT INTO ' . $table;

        if (is_array($this->_values)){
            // Callback for quoting values
            $quote = array($this->quoter, 'quote');
            $columns = array();
            $groups = array();
            foreach ($this->_values as $group){
                foreach ($group as $i => $value){
                    if($this->_columns && !in_array($i, $this->_columns)){
                        unset($group[$i]);
                        continue;
                    }
                    if (is_string($value) AND isset($this->_parameters[$value])){
                        $group[$i] = $this->_parameters[$value];
                    }
                }

                if(!$columns){
                    $columns = array_keys($group);
                }

                $groups[] = '(' . implode(', ', array_map($quote, $group)) . ')';
            }

            if(!$columns){
                throw new SqlException('Insert error, column is null');
            }

            // Add the column names
            $query .= ' ('.implode(', ', array_map(array($this->quoter, 'quoteIdentifier'), $columns)).') ';
            // Add the values
            $query .= 'VALUES '.implode(', ', $groups);

        }else{
            // Add the column names
            $query .= ' ('.implode(', ', array_map(array($this->quoter, 'quoteIdentifier'), $this->_columns)).') ';
            // Add the sub-query
            $query .= (string) $this->_values;
        }

        return $query;
    }

    public function reset(){
        $this->_table = NULL;

        $this->_columns =
        $this->_values  = array();
        $this->_parameters = array();
        return $this;
    }

} // End Insert
