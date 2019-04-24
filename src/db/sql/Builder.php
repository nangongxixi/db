<?php

namespace j\db\sql;

use Exception;

class Builder{
    /**
     * @param $value
     * @return mixed
     * @throws Exception
     */
    private static function filter ($value){
        if (is_array($value) || is_object($value) && !method_exists($value, "__toString")) {
            throw(new Exception('sql_formart_value : value type is invalid'));
        }

        if(is_int($value) && preg_match('/[0-9]+/', $value)){
        } elseif(is_null($value)) {
            $value = 'null';
        } else {
            $search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
            $replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
            $value = "'" .  str_replace($search, $replace, $value) . "'";
        }

        return $value;
    }

    /**
     * put your comment there...
     *
     * @param string $table
     * @param string $where
     * @param string $fields
     * @return string
     */
    static function select ($table, $where, $fields = '*'){
        if(!$fields){
            $fields = '*';
        }
        if(is_array($fields)){
            $fields = implode(',', $fields);
        }
        return sprintf('SELECT %s FROM %s WHERE 1 %s', $fields, $table, $where);
    }

    /**
     * @param $table
     * @param $data
     * @param array $fields
     * @param bool|false $mul
     * @return string
     * @throws 
     */
    static function insert ($table, $data, $fields = array(), $mul = false){
        if(!$fields){
            $fields = array_keys($mul ? current($data) : $data);
        }elseif(is_string($fields)){
            $fields = preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY);;
        }

        if(!$mul) {
            $data = array($data);
        }

        $valuesAll = [];
        foreach($data as $row){
            $values = $sp = '';
            foreach ($fields as $filed) {
                $values .= $sp . static::filter($row[$filed]);
                $sp = ',';
            }
            $valuesAll[] = "({$values})";
        }

        return sprintf('INSERT INTO %s (%s) VALUES %s',
            $table,
            "`" . implode('`,`', $fields) . "`",
            implode(",", $valuesAll)
        );
    }

    static function update ($table, $cond, $data, $fields = array()){
        if(!$fields) {
            $fields = array_keys($data);
        }elseif(is_string($fields)) {
            $fields = preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY);;
        }

        $values = $sp = '';
        foreach ($fields as $filed) {
            $values .= "{$sp}{$filed} = " . static::filter(isset($data[$filed]) ? $data[$filed] : null);
            $sp = ',';
        }

        return sprintf(
            'UPDATE %s SET %s WHERE 1 %s',
            $table, $values, $cond
        );
    }

    static function delete($table, $where){
        return sprintf(
            'DELETE FROM %s WHERE 1 %s',
            $table,
            $where
        );
    }
}
