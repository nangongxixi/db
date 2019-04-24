<?php

namespace j\model;

use j\base\Config;
use j\db\Dao;
use j\event\Event;
use j\event\TraitManager;
use j\di\ContainerAwareInterface;
use j\di\ContainerAwareTrait;
use j\model\Model;
use j\tool\Validator;


/**
 * Class Service
 * @package j\model
 * @property Dao $dao
 */
abstract class Service implements ContainerAwareInterface  {

    use TraitManager;
    use ContainerAwareTrait;

    const SCENE_NORMAL = 1;
    const SCENE_USER = 2;
    const SCENE_MANAGER = 4;
    const SCENE_SUPPER = 8;

    protected $scene = self::SCENE_USER;

    /**
     * @param $value
     * @return int
     */
    protected function isScene($value){
        return $this->scene > $value;
    }

    /**
     * @param int $scene
     */
    public function setScene($scene) {
        $this->scene = $scene;
    }


    abstract protected function getName();

    /**
     * @param $name
     * @return mixed|null
     */
    function __get($name) {
        switch ($name){
            case 'config' :
                return $this->getService('config');

            case 'dao' :
                return $this->dao = $this->getService('dao', $this->getName());
        }

        return null;
    }
}