# Dao or tableGateway
*   import data access layer

# Install

```
composer install scalpel/db
```

# Adapter
Query agent, query sql statement

    ## Profiler
    Log and profiler all sql

# Driver
Database connection, real query sql

    ## mysql\Mysql
    Mysql driver
    
    ## mysql\ResultSet
    Query select statement's result
 
# Sql
Create sql statement, Depend sql\InterfaceQuoter

# Table
CURD for a table,

*   add( Model / id )
*   delete() 
*   update()
*   find()
*   info()
*   count()


# ORM
*   table初始化关系定义
*   在ResultSet使用call插件机制动态为模型增加功能
*   在Relation对象中将方法注入Model对象
*   在哪一层实现, 在Table层实现，未绑定模型的绑定Model对象

<pre>
table与table的关联
配置：
    $conf['detail'] = array(
        'type' => JRelations::HAS_ONE,
        'table' => 'knowledge_cnt',
        'forkey'  => 'info_id',
        'onUpdate' => [
            field => ["content", "#id#", "url"],
            autoUpdate => 1
        ],
        'onSelect' => [
            alongField => ['content'],  // 直接注入到对象的prototype
            field => '*',
            mapName => 'knDetail', // deafult getDetail
            ]
        );

table 中定义 JRelations
    判断插入关联，
    判断删除关联，
    判断更新关联
    
查询注入 
    hasOne的定义, 
    getXxx() -> 未定义 -> 查询 $this->relations->get(this->id, Xxx关系)
        返回是数组？对象？还是字段

hasMany的定义
    todo...

phalcon参考
    $this->belongsTo('product_types_id', 'ProductTypes', 'id', array(
        'reusable' => true
    ));
    自己的id, 关联的对象, 对象的id, 配置参数
    
</pre>

# Todo
*  √ 增加读取分离， 如何实现，置入Adapter或是Driver_
*  √ 查询缓存
*  √ ORM实现
*  √ 应用层的分库分表
*   事务


## 已知问题

*   table必须要有id 主键， 没有id, model更新将不达预期，使用model.setIdentifier(id)设置

