<?php

namespace j\db;

use j\event\TraitManager;

trait TraitDaoUpdate {

    use TraitManager;

    /**
     * @return Table
     */
    abstract protected function getTable();

    /**
     * @return Table|null
     */
    protected function getTableDetail(){
        return;
    }

    /**
     * @param array $data
     * @return int
     */
    public function insert(array $data){
        $this->trigger('insert.before', $data,  $this);

        $info_id = $this->getTable()->insert($data);

        $this->trigger('insert.after', $info_id, $data, $this);

        if(!$info_id){
            return false;
        }

        if($table = $this->getTableDetail()){
            $infoData['info_id'] = $info_id;
            $infoData['content'] = $data['content'];
            $table->insert($infoData);
        }

        return $info_id;
    }

    public function update($data, $id, $where = array(), $mask = 3){
        $rs = false;
        if($mask & 1){
            $this->trigger('update.before', [$id, $data, $where], $this);

            $where['id'] = $id;
            $rs = $this->getTable()->update($data, $where);

            $this->trigger('update.after', $rs, [$id, $data, $where], $this);
        }

        if($mask & 2){
            $table = $this->getTableDetail();
            if($table && isset($data['content'])){
                $cond['info_id'] = $id;
                $rs = $table->update($data, $cond) || $rs;
            }
        }
        return $rs;
    }

    public function delete($id, $where = array()){
        $this->trigger('delete.before', [$id, $where], $this);

        if($table = $this->getTableDetail()){
            $cond['info_id'] = $id;
            $table->delete($cond);
        }

        $where['id'] = $id;
        $rs = $this->getTable()->delete($where);
        $this->trigger('delete.after', $rs, [$id, $where], $this);

        return $rs;
    }
}