<?php
namespace j\db;

use j\db\sql\Select;
use j\db\sql\Insert;
use j\db\sql\Expression;
use j\db\sql\Delete;
use j\db\sql\Update;
use j\db\sql\QuoterInterface;
use j\db\sql\Quoter;

class SqlFactory {
    const SELECT = 1;
    const DELETE = 2;
    const UPDATE = 3;
    const INSERT = 4;

    /**
     * @var string|array
     */
    protected $table;

    /**
     * @var QuoterInterface
     */
    protected $quoter;

    /**
     * @param $table
     * @param Adapter $adapter
     */
    function __construct($table, Adapter $adapter = null){
        $this->table = $table;
        if(!$adapter){
            $this->quoter = sql\Quoter::getInstance();
        }else{
            $this->quoter = $adapter->getQuoter();
        }
    }

    /**
     * @param null $table
     * @return Select
     */
    public function select($table = null){
        $instance = new Select($table ?: $this->table);
        $instance->setQuoter($this->quoter);
        return $instance;
    }

    /**
     * @param null $table
     * @return Insert
     */
    public function insert($table = null){
        $instance =  new Insert($table ?: $this->table);
        $instance->setQuoter($this->quoter);
        return $instance;
    }

    /**
     * @param null $table
     * @return Update
     */
    public function update($table = null){
        $instance = new Update($table ?: $this->table);
        $instance->setQuoter($this->quoter);
        return $instance;
    }

    /**
     * @param null $table
     * @return Delete
     */
    public function delete($table = null){
        $instance = new Delete($table ?: $this->table);
        $instance->setQuoter($this->quoter);
        return $instance;
    }

    /**
     * @param $string
     * @return Expression
     */
    public static function expr($string){
        return new Expression($string);
    }
}
