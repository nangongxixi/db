<?php
namespace j\db\sql;

/**
 * Class SqlAbstract
 * @package j\db\sql
 */
abstract class SqlAbstract {

    /**
     * @var QuoterInterface
     */
    protected $quoter = null;

    // Quoted query parameters
    protected $_parameters = array();

    /**
     * @param $key
     * @return  QuoterInterface
     */
    public function __get($key){
        if($key == 'quoter'){
            $this->quoter = Quoter::getInstance();
            return $this->quoter;
        }
        return null;
    }

    public function setQuoter(QuoterInterface $quoter){
        $this->quoter = $quoter;
    }

    /**
     * Bind a variable to a parameter in the query.
     *
     * @param   string $param parameter key to replace
     * @param   mixed $var  variable to use
     * @return SqlAbstract
     */
    public function bind($param, & $var){
        // Bind a value to a variable
        $this->_parameters[$param] =& $var;
        return $this;
    }

    /**
     * Set the value of a parameter in the query.
     *
     * @param   string $param  parameter key to replace
     * @param   mixed $value   value to use
     * @return SqlAbstract
     */
    public function param($param, $value){
        // Add or overload a new parameter
        $this->_parameters[$param] = $value;
        return $this;
    }

    /**
     * Compiles an array of JOIN statements into an SQL partial.
     *
     * @param   object $joins  JDatabase instance
     * @param   array   join statements
     * @return  string
     */
    protected function _compile_join(array $joins){
        $statements = array();

        foreach ($joins as $join){
            // Compile each of the join statements
            $statements[] = $join->compile();
        }

        return implode(' ', $statements);
    }

    /**
     * Compiles an array of conditions into an SQL partial. Used for WHERE
     * and HAVING.
     *
     * @param   array $conditions  condition statements
     * @return  string
     */
    protected function _compile_conditions(array $conditions){
        $last_condition = NULL;

        $sql = '';
        foreach ($conditions as $group){
            // Process groups of conditions
            foreach ($group as $logic => $condition){
                if ($condition === '('){
                    if ( ! empty($sql) AND $last_condition !== '(')	{
                        // Include logic operator
                        $sql .= ' '.$logic.' ';
                    }
                    $sql .= '(';
                }elseif ($condition === ')'){
                    $sql .= ')';
                }else{
                    if ( ! empty($sql) AND $last_condition !== '('){
                        // Add the logic operator
                        $sql .= ' '.$logic.' ';
                    }

                    // Split the condition
                    list($column, $op, $value) = $condition;

                    // special condition
                    if($column instanceof Expression){
                        $sql .= $column;
                        continue;
                    }

                    if ($value === NULL){
                        if ($op === '='){
                            // Convert "val = NULL" to "val IS NULL"
                            $op = 'IS';
                        }elseif ($op === '!='){
                            // Convert "val != NULL" to "valu IS NOT NULL"
                            $op = 'IS NOT';
                        }
                    }

                    // JDatabase operators are always uppercase
                    $op = strtoupper($op);

                    if ($op === 'BETWEEN' AND is_array($value)){
                        // BETWEEN always has exactly two arguments
                        list($min, $max) = $value;

                        if (is_string($min) AND array_key_exists($min, $this->_parameters)){
                            // Set the parameter as the minimum
                            $min = $this->_parameters[$min];
                        }

                        if (is_string($max) AND array_key_exists($max, $this->_parameters)){
                            // Set the parameter as the maximum
                            $max = $this->_parameters[$max];
                        }

                        // Quote the min and max value
                        $value = $this->quoter->quote($min) . ' AND ' . $this->quoter->quote($max);
                    }else{
                        if(is_array($value) && $op == '='){
                            $op = 'IN';
                        }

                        if (is_string($value) AND array_key_exists($value, $this->_parameters)){
                            // Set the parameter as the value
                            $value = $this->_parameters[$value];
                        }

                        // Quote the entire value normally
                        $value = $this->quoter->quote($value);
                    }

                    // Append the statement to the query
                    $sql .= $this->quoter->quoteIdentifier($column) . ' ' . $op . ' ' . $value;
                }

                $last_condition = $condition;
            }
        }

        return $sql;
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

    /**
     * Compiles an array of ORDER BY statements into an SQL partial.
     *
     * @param   array $columns  sorting columns
     * @return  string
     */
    protected function _compile_order_by(array $columns){
        $sort = array();
        foreach ($columns as $group){
            list ($column, $direction) = $group;

            if ( ! empty($direction)){
                // Make the direction uppercase
                $direction = ' '.strtoupper($direction);
            }

            $sort[] = $this->quoter->quoteIdentifier($column) . $direction;
        }

        return 'ORDER BY ' . implode(', ', $sort);
    }


    public function __toString(){
        return $this->compile();
    }

    /**
     * Reset the current builder status.
     *
     * @return  $this
     */
    abstract public function reset();

    /**
     * Compile the SQL query and return it. Replaces any parameters with their
     * given values.
     *
     * @return  string
     */
    abstract public function compile();
}
