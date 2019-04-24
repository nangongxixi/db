<?php

namespace j\db;

trait TraitDaoQuery {

    /**
     * @return Table
     */
    abstract protected function getTable();
    abstract protected function normalizesCond($cond);

    /**
     * @param array $where
     * @param \j\view\tag\Pager $pager
     * @return driver\mysql\ResultSet
     */
    public function find(array $where = array(), $pager = null){
        if($pager){
            $pager->setTotal($this->count($where));
            $where['_offset'] = $pager->start;
            $where['_limit'] = $pager->nums;
        }
        return $this->getTable()->find($this->normalizesCond($where));
    }

    public function count(array $where = array()){
        return $this->getTable()->count($where);
    }

    public function getInfo($where){
        if(is_numeric($where)){
            $where = array('id' => $where);
        }
        return $this->find($where)->current();
    }

    public function getNext($id){
        $select = $this->getTable()->getSql()->select();
        $select->where('id', '>', $id);
        $select->limit(1);
        return $this->getTable()->execute($select)->asObject('Baike')->current();
    }

    public function getPre($id){
        $select = $this->getTable()->getSql()->select();
        $select->where('id', '<', $id);
        $select->order_by('id', 'desc');
        $select->limit(1);
        return $this->getTable()->execute($select)->asObject('Baike')->current();
    }
}