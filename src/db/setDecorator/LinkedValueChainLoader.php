<?php

namespace j\db\setDecorator;

use j\db\ResultSet;
use j\db\SetDecoratorInterface;
use j\model\ValueLoaderChain;
use j\model\ValueLoaderInterface;

/**
 * Class LinkedValueChainLoader
 * @package j\db\setDecorator
 */
class LinkedValueChainLoader
    extends ValueLoaderChain
    implements SetDecoratorInterface {

    /**
     * @var ValueLoaderInterface[]
     */
    protected $chain = [];


    function decorate(ResultSet $resultSet)
    {
        foreach($this->chain as $loader){
            if($loader instanceof SetDecoratorInterface){
                $loader->decorate($resultSet);
            }
        }
    }
}