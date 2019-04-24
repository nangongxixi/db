<?php

namespace j\db\relation;

use j\db\Dao;
use j\db\Table;
use Exception;
use j\model\EnableDaoInterface;
use j\model\Model;

/**
 * Class Relations
 * @package j\db\relation
 */
class Relations {

    const HAS_ONE = 1;
    const HAS_MANY = 2;
    const MANY_HAS_MANY = 3;

    /**
     * @var Base[]
     */
    protected $relations = [];

    /**
     * @var Dao
     */
    protected $context;

    /**
     * Relations constructor.
     * @param Dao $context
     */
    public function __construct($context) {
        $this->context = $context;
    }

    /**
     * @param string $key
     * @param $type
     * @param $conf
     * @throws Exception
     */
    protected function add($key, $type, $conf){
        if(is_object($conf)){
            $this->relations[$key] = $conf;
            return;
        }

        $instance = null;
        switch ($type) {
            case self::HAS_ONE :
                $instance =  new HasOne($this->context, $conf);
                break;
            case self::HAS_MANY :
                $instance =  new HasMany($this->context, $conf);
                break;
            case self::MANY_HAS_MANY :
                $instance =  new ManyHasMany($this->context, $conf);
                break;
            default :
                throw new Exception('Invalid relation type');
        }

        $this->relations[$key] = $instance;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key){
        return $this->relations[$key];
    }

    /**
     * @param $key
     * @param array|Base $relation
     */
    public function hasOne($key, $relation){
        $this->add($key, self::HAS_ONE, $relation);
    }

    /**
     * @param $key
     * @param array|Base $relation
     */
    public function hasMany($key, $relation) {
        $this->add($key, self::HAS_MANY, $relation);
    }

    /**
     * @param $key
     * @param array|Base $relation
     */
    public function manyHasMany($key, $relation) {
        $this->add($key, self::MANY_HAS_MANY, $relation);
    }

    /**
     * @return mixed
     */
    public function getKeys(){
        return array_keys($this->relations);
    }

    /**
     * @param \j\db\ResultSet $result
     */
    function extendResult($result){
        $result->setExtend(function($info){
//            if($info instanceof EnableDaoInterface){
//                $info->setDao($this);
//            }
            if(!($info instanceof Model)){
                return;
            }

            foreach($this->relations as $key => $item) {
                $info->regCall('get' . ucfirst($key), function($options = []) use($key, $info, $item) {
                    return $item->load($info, $options);
                });

                if($item instanceof HasMany){
                    $info->regCall('count' . ucfirst($key), function($options = []) use($item, $info) {
                        return $item->count($info, $options);
                    });
                }

                if($item instanceof ManyHasMany){
                    $info->regCall('getVia' . ucfirst($key), function($options = []) use($item, $info) {
                        return $item->getViaResult($info, $options);
                    });
                }
            }
        });
    }
}



