<?php

namespace j\db\sql;

class Join extends SqlAbstract {

    // Type of JOIN
    protected $_type;

    // JOIN ...
    protected $_table;

    // ON ...
    protected $_on = array();

    /**
     * @param $table
     * @param null $type
     */
    public function __construct($table, $type = NULL){
        // Set the table to JOIN on
        $this->_table = $table;

        if ($type !== NULL){
            // Set the JOIN type
            $this->_type = (string) $type;
        }

    }

    /**
     * Adds a new condition for joining.
     *
     * @param   mixed $c1  column name or array($column, $alias) or object
     * @param   string $op  logic operator
     * @param   mixed $c2  column name or array($column, $alias) or object
     * @return  Join;
     */
    public function on($c1, $op, $c2){
        $this->_on[] = array($c1, $op, $c2);
        return $this;
    }

    /**
     * Compile the SQL partial for a JOIN statement and return it.
     *
     * @return  string
     */
    public function compile(){
        if ($this->_type){
            $sql = strtoupper($this->_type).' JOIN';
        }else{
            $sql = 'JOIN';
        }

        // Quote the table name that is being joined
        $sql .= ' '. $this->quoter->quoteTable($this->_table) . ' ON ';

        $conditions = array();
        foreach ($this->_on as $condition){
            // Split the condition
            list($c1, $op, $c2) = $condition;

            if ($op){
                // Make the operator uppercase and spaced
                $op = ' '.strtoupper($op);
            }

            // Quote each of the identifiers used for the condition
            $conditions[] = $this->quoter->quoteIdentifier($c1) . $op . ' ' .$this->quoter->quoteIdentifier($c2);
        }

        // Concat the conditions "... AND ..."
        $sql .= '(' . implode(' AND ', $conditions) . ')';

        return $sql;
    }

    public function reset(){
        $this->_type =
        $this->_table = NULL;
        $this->_on = array();
    }

} // End JDQBJoin
