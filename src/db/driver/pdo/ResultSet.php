<?php

namespace j\db\driver\pdo;

use j\db\ResultSet as Base;

/**
 * Class ResultSet
 * @package j\db\driver\mysqli
 */
class ResultSet extends Base
{
    /**
     * @var \PDOStatement
     */
    protected $result;

    /**
     *
     */
    protected function init() {
        // Find the number of rows in the result
        if($this->result instanceof \PDOStatement){
            $this->total_rows = $this->result->rowCount();
        }else{
            $this->total_rows = 0;
        }
    }

    /**
     * @param int $position
     * @return boolean
     */
    function seek($position){
        if($this->offsetExists($position)){
            $this->current_row = $position;
            return true;
        } else {
            return false;
        }
    }

    public function close(){
        $this->result->closeCursor();
    }

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @return array|mixed|object
     */
    protected function fetchRow(){
        if($this->total_rows <= 0){
            return [];
        }

        // Return an array of the row
        if(isset($this->cache[$this->current_row])){
            $row = $this->cache[$this->current_row];
        } else {
            $row = $this->result->fetch(\PDO::FETCH_ASSOC);
            $this->cache[$this->current_row] = $row;
        }

        return $row;
    }
} // End JDR

