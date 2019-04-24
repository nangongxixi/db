<?php

namespace j\db\mapper;

/**
 * Class DataMapper
 * @package j\db\mapper
 */
class DataMapper implements DataMapperInterface {

    protected $attrs = [];

    /**
     * @var DataTypeFactory
     */
    protected $typeFactory;

    /**
     * DataMapper constructor.
     * @param array $attrs
     * @param DataTypeFactory $typeFactory
     */
    public function __construct(array $attrs, DataTypeFactory $typeFactory = null){
        $this->setAttrs($attrs);
        $this->typeFactory = $typeFactory ?: DataTypeFactory::getInstance();
    }

    /**
     * @param $attrs
     */
    public function setAttrs($attrs){
        foreach($attrs as $key => $define){
            $this->setAttr($key, $define);
        }
    }

    /**
     * @param string $key
     * @param array $attrs
     */
    public function setAttr($key, $attrs){
        if(is_string($attrs)){
            $attrs = [$attrs, []];
        } else if(!is_array($attrs) || !isset($attrs[0])){
            throw new \InvalidArgumentException();
        }

        if(!isset($attrs[1])){
            $attrs[1] = [];
        }

        $this->attrs[$key] = $attrs;
    }

    protected function normalize($data, $method) {
        foreach($this->attrs as $key => $attr){
            if(!isset($data[$key]) && !key_exists($key, $data)){
                continue;
            }

            $data[$key] = $this->typeFactory
                ->get($attr[0])
                ->$method($data[$key], $attr[1]);
        }
        return $data;
    }

    /**
     * @param array|\ArrayAccess $data
     * @return array
     */
    function store($data){
        return $this->normalize($data, 'store');
    }

    /**
     * @param array|\ArrayAccess $data
     * @return array
     */
    function restore($data){
        return $this->normalize($data, 'restore');
    }
}