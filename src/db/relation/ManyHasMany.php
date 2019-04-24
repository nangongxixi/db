<?php

namespace j\db\relation;

use j\db\exception\RuntimeException;
use j\db\Table;
use j\model\Model;
use Exception;
use j\tool\ArrayUtils;

/**
 * Class RelationHasMany
 * load()
 * add()
 * update()
 * delete()
 * reset()
 * @package j\db\relation
 */
class ManyHasMany extends HasMany  {
    /**
     * @var Table
     */
    protected $viaTable;

    /**
     * @var []
     */
    protected $viaOptions;

    /**
     * ManyHasMany constructor.
     * @param \j\db\Dao $context
     * @param $config
     * @throws Exception;
     */
    public function __construct($context, $config) {
        if(!isset($config['viaTable'])){
            throw new Exception("Invalid config for manyHasMany");
        }

        $this->viaOptions = $config['viaTable'];

        if(!isset($this->viaOptions['keys'])){
            throw new Exception("Invalid viaTable[keys] for manyHasMany");
        }

        if(!isset($this->viaOptions['table'])){
            throw new Exception("Invalid viaTable[table] for manyHasMany");
        }

        parent::__construct($context, $config);
    }

    /**
     * 查询关联信息
     * @param Model $model
     * @param [] $options
     * @return \j\db\driver\mysqli\ResultSet|array
     */
    function load($model, $options = []){
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        if(!$cond){
            // 中间表查询结果集为空
            return [];
        }

        $rs = $dao->find($cond);

        if(!isset($this->viaOptions['bindViaInfo']) || !$this->viaOptions['bindViaInfo']){
            return $rs;
        }

        // add Model::getViaInfo()
        $targetKey = $this->viaOptions['keys'][0];
        $bindViaObject = ArrayUtils::gav($this->viaOptions, 'bindViaObject', '');
        $rs->setExtend(function($info) use($model, $targetKey, $bindViaObject){
            if(!($info instanceof Model)){
                return;
            }

            $info->regCall('getViaInfo', function() use($info, $model, $targetKey, $bindViaObject){
                $rs = $this->getViaResult($model, [
                    $targetKey => $info[$targetKey],
                    '_fields' => ['*'],
                    '_limit' => 1
                    ]);
                if($bindViaObject){
                    $rs->asObject($bindViaObject);
                }
                return $rs->current();

            });
        });
        return $rs;
    }

    /**
     * @param $model
     * @param array $options
     * @return string
     * @throws \Exception
     */
    function count($model, $options = []) {
        $dao = $this->getDao();
        $cond = $this->buildCond($model, $options);
        if(!$cond){
            // 中间表查询结果集为空
            return 0;
        }

        return $dao->count($cond);
    }

    /**
     * @param Model $model
     * @param array $options 调用时传入
     * @return array
     */
    protected function buildCond($model, $options = []) {
        $keyMap = $this->viaOptions['keys'];
        $ids = $this->getViaResult($model, $options)
            ->toArray(null, $keyMap[0]);
        if(!$ids){
            return [];
        }

        $cond = [];
        if(isset($this->options['cond'])){
            $cond = $this->options['cond'];
        }
        $cond[$this->keyMap[0]] = $ids;
        return $cond;
    }

    /**
     * @param Model $model
     * @param array $options
     * @return \j\db\driver\mysqli\ResultSet
     */
    public function getViaResult($model, $options = []){
        $viaOptions = $this->viaOptions;

        if(isset($viaOptions['on'])){
            $options = array_merge($viaOptions['on'], $options);
        }

        $keyMap = $viaOptions['keys'];
        if(is_null($model[$keyMap[1]])){
            throw new RuntimeException("Via table condition is null");
        }

        $options[$this->keyMap[1]] = $model[$keyMap[1]];
        return $this->getViaTable()->find($options);
    }

    /**
     * @return Table|null
     */
    protected function getViaTable(){
        if(isset($this->viaTable)){
            return  $this->viaTable;
        }

        $conf = $this->viaOptions;
        /** @var Table $table */
        $table = $this->context->getService('dbTable', $conf['table']);
        if(isset($conf['pk'])){
            $table->setPrimkey($conf['pk']);
        }

        if(isset($conf['define'])) {
            call_user_func($conf['define'], $table);
        }

        return $this->viaTable = $table;
    }
}
