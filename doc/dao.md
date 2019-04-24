
# Dao概述

dao类用于与数据接口的封闭, 
存储model对象到数据库, 并负责读取数据库中的数据映射为Model对象, 有数据仓储的味道, 
一个典型的Dao负责以下工作(不必须全部实现):

## 声明表名

实现getTable()方法, 并设置简单查询映射

```

/**
 * @return \j\db\Table
 */
public function getTable(){
    $table = table($this->tableName);
    $table->resultAsObject($this->modelClass);
    $table->setWhereConf('searchKey', ['title', 'like']);
    $table->setWhereConf('idr', ['id', '>']);
    $table->setWhereConf('idl', ['id', '<']);
    $table->setWhereConf('gcid', ['class_id', 'like "%value%%"']);
    return $table;
}

```

## 解析数据排序规则

```
    /**
     * 解析排序
     *
     * @param Select $qer
     * @param mixed $cond
     */
    protected function parseOrder($qer, $cond) {
        if(!isset($cond['_order'])){
            return;
        }

        switch ($cond['_order']) {
            case 'honest':
                $qer->order_by('honest', 'DESC');
                $qer->order_by('id', 'DESC');
                break;
            default:
                $qer->order_by('id', 'DESC');
                break;
        }

    }

```

###  解析查询字典映射为查询条件

```
    /**
     * 解析列表条件
     *
     * @param Select $qer
     * @param mixed $cond
     */
    protected function parseCond($qer, $cond){
        if(!is_array($cond)){
            return;
        }

        // is_rmd -- 是推荐
        if(isset($cond['is_rmd']) && $cond['is_rmd']){
            $qer->where('recommend', '>', 0);
            unset($cond['is_rmd']);
        }

        // is not 001
        if(isset($cond['is_not_001']) && $cond['is_not_001']){
            $qer->where('weblabel', '<>', '1');
            unset($cond['is_not_001']);
        }
    }
```

###  验证存储/更新数据合理性

```
    /**
     * @override
     * @param $data
     * @param Model $model
     * @return bool
     * @throws
     */
    function validate($data, $model = null){
        $validator = $this->getValidator($data);
        $validator
            ->rule('title', 'len[4,75]', '标题应该在4-75个字之间')
            ->rule('class_id', 'len[1, 30]', '请选择类别')
            ->rule('actMomey', 'len[1, 50]', '请选择基本投资额')
            ->requireFields(
                'title', 'class_id', 'actMomey', 'locate',
                'store', 'fdate', 'content'
            );
        return $validator->check($model->isNew());
    }

```


### 装饰结果集

处理model关联数据, 有两种关联方式:

1. 基于结果集ValueLoader的关联

```
    /**
     * @inheritdoc
     * @override
     */
    protected function getListSetDecorator($result, $where)
    {
        // 由于ResultSet的迭代中不允许再次迭代, 必须要先获取ids
        $shopIds = $result->toArray(null, 'shop_id', false, true);
        $shopIds = array_unique($shopIds);

        $brandIds = $result->toArray(null, 'brand_id', false, true);
        $brandIds = array_unique($brandIds);

        $ids = $result->toArray(null, 'id', false, true);
        $ids = array_unique($ids);

        // 初始化, 好处是用时再初始化
        $vm = new ValueManager([], function(ValueManager $vm) use ($shopIds, $ids){
            $loads = [];

            // shop
            $loads[] = $shop = $this->mkSetOneLoader(
                'shop', 'shop_id',
                ShopDao::class, $shopIds
            );
            $shop->setChildField(['company', 'linkman', 'phone']);

            // detail
            $loads[] = $detail = $this->mkSetOneLoader(
                'detailInfo', 'id',
                DetailDao::class, $ids
                );
            $detail->setChildField([
                'agent_id', 'address', 'phone', 'fax', 'email', 'linkman',
                'locate', 'store', 'actMomey',
                'actBaozen', 'actJoinMoney', 'actBrandArea',
                'actYield', 'actBackCycle', 'actConcessionRate',
                'actCompactLimit', 'actShopJoinNums', 'actTimeLimit',
                'actDevelopKind', 'actKind', 'actBrand',
                'actMsgPro', 'homepage'
            ]);
            $vm->addLoader($loads);
        });

        $vm->decorateList($result);
    }
```

2. 基于er关系映射

```
    public function relations(){
        $rm = new Relations($this);
        $rm->manyHasMany('sales', [
            'model' => $this->getService(SaleUserDao::class),
            'keys' => ['sale_uid', 'prod_uid'],
            'viaTable' => [
                'table' => 'chnl_user_map',
                'keys' => ['sale_uid', 'prod_uid'],
                'on' => [
                    '_fields' => ['sale_uid']
                ],
            ],
            'cond' => [
                '_order' => ['id', 'desc'],
            ],
        ]);
        return $rm;
    }
```

## 字段映射 

将字段值序列化存储, 再将序列化值反序列化成程序使用字段

```

    /**
     * @return DataMapper|null
     */
    protected function getMapper(){
        if(isset($this->dataMapper)){
            return $this->dataMapper;
        }

        $defines = [
            'create_info' => 'array',
            'note' => 'array',
            'status' => 'int',
            'expire' => 'dateTime',
            'cdate' => ['dateTime', [
                'autoCreate' => true,
                'format' => 'Y-m-d H:i:s'
            ]],
        ];
        $this->dataMapper = new DataMapper($defines);
        return $this->dataMapper;
    }

```