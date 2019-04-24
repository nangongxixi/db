<?php
namespace j\db;

use j\db\setDecorator\HasOneLinkedValueLoader;
use j\db\setDecorator\SetValueManager;
use j\db\sql\SqlWhere;
use j\di\Container;
use j\error\Error;
use j\event\Event;
use j\event\TraitManager;
use j\di\ContainerAwareInterface;
use j\di\ContainerAwareTrait;
use j\model\Model;
use j\model\ValueLoaderInterface;
use j\tool\Validator;

/**
 * 数据表网关对象
 * 重点实现行对象模型的查询及更新
 *
 * Class Dao|Repository
 * @package j\db
 *
 * @property int $primaryId
 * @property Table $table
 * @property relation\Relations $relations
 */
abstract class Dao implements ContainerAwareInterface
{
    use TraitManager;
    use ContainerAwareTrait;

    const EVENT_INSERT_BEFORE = 'dao.add.pre';
    const EVENT_INSERT_AFTER = 'dao.add.post';
    const EVENT_UPDATE_BEFORE = 'dao.update.pre';
    const EVENT_UPDATE_AFTER = 'dao.update.post';
    const EVENT_DELETE_BEFORE = 'dao.delete.pre';
    const EVENT_DELETE_AFTER = 'dao.delete.post';

    /**
     * @return \j\db\Table
     */
    abstract public function getTable();

    /**
     * @var self[]
     */
    protected static $instance;

    /**
     * @var array
     */
    protected $defaultCond = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var bool
     */
    public $enableFindAll = false;


    const QUERY_TYPE_READ = 1;
    const QUERY_TYPE_WRITE = 2;
    const QUERY_TYPE_DEFAULT = 3;

    /**
     * @param $name
     * @return mixed|Table
     * @throws \Exception
     */
    function __get($name) {
        switch ($name){
            case 'primaryId':
                $this->primaryId = $this->table->getPk();
                if(!$this->primaryId){
                    throw new \Exception("Invalid primary key");
                }
                return $this->primaryId;

            case 'table':
                return $this->table = $this->getTable();

            case 'relations' :
                return $this->relations = $this->relations();
        }

        return null;
    }

    /**
     * @param $data
     * @param array $rules
     * @throws
     * @return Validator
     */
    protected function getValidator($data = [], $rules = []){
        $validator = new Validator($data);
        if($rules){
            $validator->rules($rules);
        }
        return $validator;
    }

    /**
     * @return static
     */
    public static function getInstance(){
        $key = get_called_class();
        if(isset(static::$instance[$key])){
            return static::$instance[$key];
        }
        static::$instance[$key] = new static();
        return static::$instance[$key];
    }

    protected function event($name, $args = []){
        return new Event($name, $args);
    }

    /**
     * @param $err
     * @param string $key
     */
    function setError($err, $key = ''){
        if(is_array($err)){
            $this->errors = array_merge($this->errors, $err);
        }else{
            $this->errors[$key] = $err;
        }
    }

    function getError(){
        return $this->errors;
    }

    /**
     * @param array $defaultCond
     */
    public function setDefaultCond($defaultCond){
        $this->defaultCond = $defaultCond;
    }

    protected function normalizesCond($cond, $queryType = self::QUERY_TYPE_DEFAULT){
	    if(is_numeric($cond)){
		    $cond = [$this->primaryId = $cond];
	    }

	    if($this->defaultCond){
            return array_merge($this->defaultCond, $cond);
        } else {
	        return $cond;
        }
    }

    /**
     * todo: add DecoratorManager
     * @param array $where
     * @param null|SetDecoratorInterface[] $extends
     * @return \j\db\driver\mysqli\ResultSet
     * @throws
     */
    public function find(array $where = array(), $extends = null)
    {
        if(is_object($extends) && method_exists($extends, 'setTotal')){
            // todo if total == 0 return empty result
            $extends->setTotal($this->count($where));
            $where['_offset'] = $extends->start;
            $where['_limit'] = $extends->nums;
        }

        $cond = $this->normalizesCond($where, self::QUERY_TYPE_READ);
        $result = $this->table->find($cond);

        if(count($result)){
            if($dataMapper = $this->getMapper()){
                $result->setDataMapper($dataMapper);
            }

            if($decorates = $this->getListSetDecorator($result, $where)){
                $result->setExtend($decorates);
            }
        }

        if(!$this->table->returnArray($where)
            && $this->table->resultAsObject()
        ){
            // 绑定关联数据
            if($this->relations){
                $this->relations->extendResult($result);
            }

            // 注入Dao 到 model
            $result->setExtend(function($row){
                if($row instanceof Model && $row->enableDao){
                    $row->setDao($this);
                }
            });
        }

        return $result;
    }

    /**
     * 装饰结果集
     * @param ResultSet $result
     * @param array $where
     * @return array|SetDecoratorInterface|SetValueManager
     */
    protected function getListSetDecorator($result, $where){
        return [];
    }

    /**
     * 集合 值管理器
     * @param ValueLoaderInterface[] $loaders
     * @return SetValueManager
     */
    protected function getValueManger($loaders = []){
        return new SetValueManager($loaders);
    }

    /**
     * 创建has-one关系连接
     * @param $class
     * @param $fieldName
     * @param $fk
     * @param $primaryKey
     * @return HasOneLinkedValueLoader
     */
    protected function createHasOneLink($class, $fieldName, $fk, $primaryKey = '')
    {
        $dao = $this->getService($class);
        return new HasOneLinkedValueLoader(
            $dao, $fieldName, $fk,
            $primaryKey ?: $dao->primaryId
        );

        /*
         * rs/ids, cond
        $ids = []; // $ids = $rs->toArray(null, $fk);
        $cond = [];
        if($ids){
            $mapDataMaker = function()use($primaryKey, $ids, $cond){
                $primaryKey = $primaryKey ?: $this->primaryId;
                $cond[$primaryKey] = $ids;
                return $this->find($cond)->toArray($primaryKey);
            };
        }
        //return new HasOneLinkedValueLoader($fieldName, $fk, $mapDataMaker);
        */
    }

    /**
     * @param array $where
     * @return string
     */
    public function count($where = array()){
        return $this->table->count($this->normalizesCond($where, self::QUERY_TYPE_READ));
    }

    /**
     * @param $id
     * @param array $where
     * @param boolean $autoCreate
     * @param array $initData
     * @return array|Model|mixed|object|\stdClass
     * @throws
     */
    public function findOne($id, $where = [], $autoCreate = false, $initData = []){
    	if(!$id){
    		throw new \Exception("Invalid id");
	    }

        $where[$this->primaryId] = $id;
        $where['_limit'] = 1;
        $where = $this->normalizesCond($where, self::QUERY_TYPE_READ);
        $model = $this->find($where)->current();

        if($model){
            if(is_array($model) && $autoCreate){
                $info = $this->createModel([], false);
                $info->exchange($model, false);
                $info->setIdentifier($id);
                $model = $info;
            }
        }else if($autoCreate){
            // create new model
            $model = $this->createModel($initData);
            $model->setIdentifier($id);
         }

        return $model;
    }




    /**
     * 得到数据映射器
     * @return null|mapper\DataMapper
     */
    protected function getMapper(){
        return null;
    }

    /**
     * 映射数据
     * @param array $data
     * @param string $do store|restore
     * @throws
     */
    protected function mapperData(& $data, $do = 'store'){
        $mapper = $this->getMapper();
        if(!$mapper){
            return;
        }

        if($do == 'store'){
            $data = $mapper->store($data);
        } elseif($do == 'restore'){
            $data = $mapper->restore($data);
        } else {
            throw new \Exception("Invalid do for mapperData");
        }
    }

    /**
     * @param Model|array $model
     * @throws
     * @return int
     */
    public function insert($model){
        $model = $this->normalizesModel($model);
        $this->trigger(self::EVENT_INSERT_BEFORE, $model);

        $data = $this->filter($model);
        if(!$this->validate($data, $model)){
            return false;
        }

        $this->mapperData($data, 'store');  // 映射字段数据
        $rs = $this->table->insert($data);
        if($rs){
            // 更新行模型状态
            $model->setIdentifier($rs);
            $model->resetDataStatus(true);
        }

        $this->trigger(self::EVENT_INSERT_AFTER, $model, $data);
        return $rs;
    }

    /**
     * @param $model
     * @return Model
     * @throws
     */
    protected function normalizesModel($model) {
        if(is_array($model)){
            $model = $this->createModel($model);
        } else if($model instanceof Model) {
            $model->setDao($this);
        } else {
            throw new \Exception("Invalid type");
        }

        return $model;
    }

    /**
     * @param $initData
     * @param bool $isNew
     * @throws
     * @return mixed|null|Object|Model
     */
    public function createModel($initData = [], $isNew = true){
        $class = $this->table->resultAsObject();
        if(!$class){
            $class = 'j\model\Model';
        }

        // create new model
        $model = Container::getInstance()->make($class, [$initData, $isNew]);
        if($model instanceof Model){
            $model->setDao($this);
            $model->setIdentifier($this->primaryId, true);

            // 装饰对象
            // todo is bug? 如果有需要主键的装饰器, 存在问题, 应该是插入后再做处理
            $result = new ResultSetCached([$model], 'create model');
            if($extends = $this->getListSetDecorator($result, [])){
                $result->setExtend($extends);
            }
            // 执行extends
            $result->current();
        }

        return $model;
    }

    /**
     * @param $data
     * @param Model|null $model
     * @return bool
     */
    public function validate($data, $model = null) {
        if(!$data) {
            Error::warning("data is empty for udpate or insert");
            return false;
        }

        if($model && $model instanceof Model){
            return $model->validate();
        }
        return true;
    }


    /**
     * @param array $cond
     * @param mixed $excludeId
     * @return bool
     */
    function exist($cond, $excludeId = null) {
        if($excludeId){
            $cond['_callback'] = function(SqlWhere $query) use($excludeId){
                $query->where($this->primaryId, '!=', $excludeId);
            };
        }
        $c = $this->table->count($cond);
        return $c > 0;
    }

    /**
     * 字段唯一性验证
     * @param array $rules [ key => [<array cond>, <string error_message>], ...]
     * @param null $excludeId
     * @return bool
     */
    public function validateUniques($rules, $excludeId = null){
        $errors = [];
        foreach($rules as $key => $rule){
            if($this->exist($rule[0], $excludeId)){
                $errors[$key] = $rule[1];
            }
        }

        if($errors){
            Error::validError($errors);
            return false;
        }

        return true;
    }

    /**
     * @param Model $model
     * @return array
     */
    protected function filter(Model $model) {
        return $model->toStore();
    }

    /**
     * @param Model $model
     * @param array $filter
     * @return bool|int
     */
    public function update(Model $model, $filter = []){
        $this->trigger(self::EVENT_UPDATE_BEFORE, $model);

        $data = $this->filter($model);
        if(!$this->validate($data, $model)){
            return false;
        }

        // filter fields
        // 不能更新模型标识符
        unset($data[$this->primaryId]);
        foreach($filter as $rs){
            unset($data[$rs]);
        }

        if(!$data){
            $this->setError('没有数据更新');
            return true;
        }

        //$where[$this->primaryId] = $model->identifier();
        $where[$this->primaryId] = $model[$this->primaryId];
        $where = $this->normalizesCond($where, self::QUERY_TYPE_WRITE);

        $this->mapperData($data, 'store');  // 映射字段数据
        $rs = $this->table->update($data, $where);
        if($rs){
            $model->resetDataStatus();
        }

        $this->trigger(self::EVENT_UPDATE_AFTER, $model, $data);
        return $rs;
    }

    /**
     * @param Model $model
     * @return bool|int
     */
    public function save(Model $model){
        if($model->isNew()){
            //trace('for insert');
            return $this->insert($model);
        } else {
            //trace('for update');
            return $this->update($model);
        }
    }

    /**
     * @param $model
     * @return bool
     * @throws \Exception
     */
    public function delete(Model $model){
        $this->trigger(self::EVENT_DELETE_BEFORE, $model);

        //$where[$this->primaryId] = intval($model->identifier());
        $where[$this->primaryId] = $model[$this->primaryId];

        $f = $this->table->delete($this->normalizesCond($where, self::QUERY_TYPE_WRITE));
        $this->trigger(self::EVENT_DELETE_AFTER, $model, $f);
        return $f;
    }

    /**
     * @param $id
     * @param $cond
     * @throws
     * @return bool
     */
    public function deleteFromId($id, $cond = []){
        $cond[$this->primaryId] = $id;
        $model = $this->findOne($id, $cond);
        if(!$model){
            return true;
        }

        if(is_array($model)){
            $model = new Model($model, true);
        }

        return $this->delete($model);
    }


    /**
     * example
     * <code>
     *
    $rm->hasOne('profiles', [
        'table' => 'adminuser_info',
        'keys' => ['user_id', 'id'],
        ]);

    $rm->hasMany('keywords', [
        'table' => 'adminuser_keywords',
        'keys' => ['uid', 'id'],
        'cond' => ['_order' => 'id']
        ]);
    $rm->hasMany('keys', [
        'model' => 'keyword',
        'keys' => ['uid', 'id'],
        ]);

    $rm->manyHasMany('images', [
        'table' => 'images',
        'keys' => ['id', 'user_id'], // targetCond[id]=v,
        'viaTable' => [
            'table' => 'admin_img',
            'keys' => ['img_id', 'id'], // viaCond[user_id]=contextModel[id]
            'on' => ['diy' => 200, '_order' => 'id'],
            'define' => function(Table $table){
                $table->setWhereConf('diy', function(Select $select, $value){
                $select->where('id', '<', $value);
            });
        }],
        'cond' => ['_order' => ['id', 'desc']],
        ]);
     * </code>
     * @return null|relation\relations
     */
    public function relations(){
        return null;
    }
}
