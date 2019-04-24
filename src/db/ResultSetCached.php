<?php

namespace j\db;

use j\model\Model;

/**
 * Cached database result.
 */
class ResultSetCached extends ResultSet
{

    public function __construct(array $result, $sql = '', $as_object = NULL) {
        parent::__construct($result, $sql, $as_object);
        // Find the number of rows in the result
        $this->total_rows = count($result);
    }

    public function __destruct() {
        // Cached results do not use resources
        unset($this->result);
    }

    public function cached() {
        return $this;
    }

    public function seek($offset) {
        if (is_numeric($offset) && $this->offsetExists($offset)){
            $this->current_row = $offset;
            return true;
        }  elseif($offset == 'count') {
            return $this->total_rows;
        } else  {
            return false;
        }
    }

    /**
     * @return array
     */
    protected function fetchRow(){
        if($this->total_rows == 0){
            return null;
        }
        return $this->result[$this->current_row];
    }
}
