
# Model对象扩展属性/方法

1. regCall(key, callback);
1. getX()
1. isX()
1. loadValue(), 变量值管理
1. _load() 重写

## Value Manger设计

varKey => varValue generator的管理, 实现var值的延迟加载及动态管理, 解决

1. 硬编码Model::_load()
2. sql查询性能问题, 结果集产生 m(行) x n(值)次查询

**传统方式:**
```
# 增加一个key, 在_load()增加一个switch case
# 如果多个row对象需要同一个key, 需要在多个对角修改_load()增加switch
function _load($key){
    switch($key) {
        case "var1":
        case "varN":
    }
}
```

改进后, model的扩展字段将允许外部注入, 在dao中直接为model增加新的关联字段

**流程:**

```
Dao 
    -> create value manager(SetValueManager) 
    -> decorator ResultSet 
    -> set vm to Model 
    -> loadKey 
    -> ValueManager 
    -> ValueLoader::getValue()

```

如何创建vm?
1. 创建vm/valueLoaders, 未触发model key, 用不上
2. 创建vm, 设置 initializer callback, 用到的时候再初始化 


**key的管理:**

1. key在model/dao/valueManger context中管理, 好处: 便于理解key在何处加载, 
以及惰性加载valueLoader对象, 以空间换可读性, Loader中可以不管理key
2. key在Loader中管理, 在Loader中管理key, 好处: 多处调用同一valueLoader不需要重复写key

改进

```
$define = [
   class => X
   arguments => []
]
```
1. vm::addLoader(loader, keys = [])
2. vm::addLoaderWithDefine(keys, define)

### Loader

供value manager 调用, 以下特性

1. 设置ValueLoaderInterface::initializer, 延迟初始化 ValueLoaderInterface, 
使用decorateDbSet()装饰结果集行对象, 不可与SetValueManager同时使用
1. addLoader: callback + keys
2. addLoader: ValueLoaderInterface, 通过loader::getKeys(), 提供可加载的key
3. addLoader: di define

## SetValueManager

结果集关联值加载, 通过SetDecoratorInterface(结果集装饰器)接口, 实现 :
1. 将SetValueManager注入到到结果集每个Model(行)对象中, 即调用model::setValueManger($this),
2. 将结果集(ResultSet)注入到每个实现 SetDecoratorInterface接口的 ValueLoaderInterface中

## Interface Api

1. ValueLoaderInterface, 值加载接口
2. ValueManager 值管理
2. SetLinkValueLoader 关联数据管理
