<?php

namespace j\db\setDecorator;

use j\db\ResultSet;
use j\db\SetDecoratorInterface;
use j\model\Model;
use j\model\ValueManager;

/**
 * 解决每个loader进行多次 extends
 * 用做对结果集中的行对象赋值 valueLoader对象
 *
 * Class SetValueManager
 * @package j\db\setDecorator
 */
class SetValueManager
    extends ValueManager
    implements SetDecoratorInterface
{

    /**
     * @param ResultSet $resultSet
     */
    function decorate(ResultSet $resultSet)
    {
        if(!$this->loaders){
            return;
        }

        foreach($this->loaders as $loader){
            if($loader instanceof SetDecoratorInterface){
                $loader->decorate($resultSet);
            }
        }

        // 设置 model value manager;
        $resultSet->setExtend(function(& $row) {
            if($row instanceof Model){
                $row->setValueManger($this);
            } else {
                foreach($this->loaders as $loader){
                    foreach($loader->getKeys() as $key){
                        $row[$key] = $loader->loadValue($row, $key);
                    }
                }
            }
        });
    }
}

