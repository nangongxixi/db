<?php

namespace j\db\relation;

use j\db\Table;
use j\db\driver\mysqli\ResultSet;

/**
 * Class MiddleTable
 * @package j\db
 */
class MiddleTable {

    public $infoKey;
    public $aidKey = '_id';

    /**
     * @var Table
     */
    protected $table;
    protected $requireKeys = array();

    /**
     * HasMany constructor.
     * @param Table $midTable
     */
    public function __construct(Table $midTable) {
        $this->table = $midTable;
    }

    /**
     * @param $infoId
     * @param int $limit
     * @param null $valueKey
     * @return array|ResultSet
     */
    function values($infoId, $limit = 50, $valueKey = null){
        $cond = [
            $this->infoKey => $infoId,
            "_limit" => $limit,
            ];
        if($valueKey){
            $cond['_fields'] = $valueKey;
            return $this->table->find($cond)->toArray($this->infoKey, $valueKey);
        } else {
            return $this->table->find($cond);
        }
    }

    function delete($infoId) {
        return $this->table->delete([
            $this->infoKey => $infoId,
        ]);
    }

    /**
     * @param $infoid
     * @param $values
     * @param bool $append
     * @return bool|int|ResultSet
     */
    function save($infoid, $values, $append = false) {
        $rs = false;
        if(!$append){
            $rs = $this->delete($infoid);
        }

        foreach ($values as $v) {
            $match = true;
            foreach ($this->requireKeys as $rk) {
                if(!isset($v[$rk]) || strlen($v[$rk]) == 0){
                    $match = false;
                    continue;
                }
            }

            if(!$match){
                continue;
            }

            $v[$this->infoKey] = $infoid;
            $rs = $this->table->insert($v) | $rs;
        }

        return $rs;
    }
}