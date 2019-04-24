<?php

namespace j\model;

use j\db\Dao;

/**
 * Class RepositoryToolTrait
 * @package j\model
 */
trait EnableDaoTrait  {

    /**
     * @var Dao
     */
    public $repository;

    /**
     * @return mixed
     */
    abstract public function identifier();

    /**
     * @return bool|int
     */
    function save(){
        if($this->identifier()){
            return $this->repository->update($this);
        } else {
            return $this->repository->insert($this);
        }
    }

    function setDao($dao){
        $this->repository = $dao;
    }
}