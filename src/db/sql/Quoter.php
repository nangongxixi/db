<?php

namespace j\db\sql;

/**
 * Class QuoterInterface
 * @package j\db\sql
 */
class Quoter implements QuoterInterface{

    protected $tablePrefix = '';
    protected $identifier = '`';

    private static $instance = null;

    /**
     * @return Quoter
     */
    public static function  getInstance(){
        if(!self::$instance){
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function getTablePrefix() {
        return $this->tablePrefix;
    }

    public function quoteTable($value) {
        // Assign the table by reference from the value
        if (is_array($value)){
            $table = $value[0];
        } else {
            $table = $value;
        }

        if (is_string($table) AND strpos($table, '.') === FALSE) {
            // Add the table prefix for tables
            $table = $this->getTablePrefix() . $table;
        }

        return $this->quoteIdentifier($table);
    }

    public function quoteIdentifier($value) {
        if ($value === '*') {
            return $value;
        } elseif (is_object($value)){
            if ($value instanceof SqlAbstract){
                // Create a sub-query
                return '('.$value->compile().')';
            }  elseif ($value instanceof Expression) {
                // Use a raw expression
                return $value->value();
            }  else{
                // Convert the object to a string
                return $this->quoteIdentifier((string) $value);
            }
        } elseif (is_array($value)) {
            // Separate the column and alias
            list ($value, $alias) = $value;
            return $this->quoteIdentifier($value).' AS '.$this->quoteIdentifier($alias);
        }

        if (strpos($value, '"') !== FALSE){
            // Quote the column in FUNC("ident") identifiers
            return preg_replace_callback('/"(.+?)"/', function($m){
                return $this->quoteIdentifier($m[1]);
            }, $value);
        }  elseif (strpos($value, '.') !== FALSE) {
            // Split the identifier into the individual parts
            $parts = explode('.', $value);

            if ($prefix = $this->getTablePrefix())  {
                // Get the offset of the table name, 2nd-to-last part
                // This works for databases that can have 3 identifiers (Postgre)
                $offset = count($parts) - 2;

                // Add the table prefix to the table name
                $parts[$offset] = $prefix . $parts[$offset];
            }

            // Quote each of the parts
            return implode('.', array_map(array($this, __FUNCTION__), $parts));
        }  else {
            return $this->identifier . $value . $this->identifier;
        }
    }

    public function quote($value){

        if ($value === NULL){
            return 'NULL';
        }elseif ($value === TRUE){
            return "1";
        }elseif ($value === FALSE){
            return "0";
        }elseif (is_int($value)){

            return (int) $value;
        }elseif (is_float($value)){
            return sprintf('%F', $value);
        }elseif(is_object($value)) {
            if ($value instanceof SqlAbstract) {
                // Create a sub-query
                return '(' . $value->compile($this) . ')';
            } elseif ($value instanceof Expression) {
                // Use a raw expression
                return $value->value();
            } else {
                // Convert the object to a string
                return $this->quote((string) $value);
            }
        }elseif(is_array($value)){
            foreach ($value as $k => $v) {
                $value[$k] = $this->quote($v);
            }
            return '(' . implode(', ', $value) .')';
        }

        return  $this->escape($value);
    }

    public function escape($value){
        $search = array("\\","\0","\n","\r","\x1a","'",'"');
        $replace = array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
        return "'" . str_replace($search, $replace, $value) . "'";
    }

    public function fields($table){
        throw new \Exception('Not support');
    }
}