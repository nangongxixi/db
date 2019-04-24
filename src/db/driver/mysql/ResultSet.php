<?php

namespace j\db\driver\mysql;

use j\db\ResultSet as Base;

/**
 * Class ResultSet
 * @package j\db\driver\mysql
 * @deprecated
 */
class ResultSet extends  Base
{
    protected function init() {
        // Find the number of rows in the result
        if(is_resource($this->result)){
            $this->total_rows = mysql_num_rows($this->result);
        }else{
            $this->total_rows = 0;
        }
    }

    public function close(){
        if (is_resource($this->result)) {
            mysql_free_result($this->result);
        }
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function seek($offset) {
        if ($this->offsetExists($offset) AND mysql_data_seek($this->result, $offset)) {
            // Set the current row to the offset
            $this->current_row = $this->internal_row = $offset;

            return TRUE;
        }  else {
            return FALSE;
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
        return mysql_fetch_assoc($this->result);
    }

    function getAssocIterator(){
        while($row = mysql_fetch_assoc($this->result)){
            yield $row;
        }
    }
} // End JDR


