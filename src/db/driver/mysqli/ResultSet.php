<?php

namespace j\db\driver\mysqli;

use j\db\ResultSet as Base;
use j\model\Model;

/**
 * Class ResultSet
 * @package j\db\driver\mysqli
 */
class ResultSet extends Base {
    /**
     * @var \mysqli_result
     */
    protected $result;

    /**
     *
     */
    protected function init() {
        // Find the number of rows in the result
        if($this->result instanceof \mysqli_result){
            $this->total_rows = $this->result->num_rows;
        }else{
            $this->total_rows = 0;
        }
    }

    public function close(){
        if ($this->result) {
            $this->result->free();
        }
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function seek($offset) {
        if ($this->offsetExists($offset) AND $this->result->data_seek($offset)) {
            // Set the current row to the offset
            $this->current_row = $this->internal_row = $offset;

            return true;
        }  else {
            return false;
        }
    }

    /**
     * @return array
     */
    protected function fetchRow()
    {
        if ($this->current_row !== $this->internal_row
            AND ! $this->seek($this->current_row)
        ){
            return [];
        }

        // Increment internal row for optimization assuming rows are fetched in order
        $this->internal_row++;
        return $this->result->fetch_assoc();
    }


    function getAssocIterator()
    {
        $current_row = $this->current_row;
        $this->seek(0);
        $data = $this->result->fetch_all(MYSQLI_ASSOC);
        $this->seek($current_row);
        return $data;
    }
} // End JDR

