<?php

namespace j\db\mapper;

use j\db\mapper\exceptions\DataTypeNotFound;

/**
 * Class DataTypeFactory
 * @package j\db\mapper
 */
class DataTypeFactory {

    /**
     * @var array
     */
    protected $types = [
    ];

    /**
     * @var static
     */
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function register($type, $define)
    {
        $this->types[$type] = $define;
        return $this;
    }

    /**
     * @param $type
     * @return DataTypeInterface
     * @throws
     */
    protected function getDefaultType($type)
    {
        $class = __NAMESPACE__ . '\\dataType\\' . ucfirst($type);
        if(!class_exists($class)){
            throw new DataTypeNotFound("Data type not found({$class})");
        }

        return new $class;
    }

    /**
     * @param $type
     * @return DataTypeInterface|null
     */
    public function get($type) {
        if ($type == 'int') {
            $type = 'integer';
        } elseif ($type == 'text') {
            $type = 'string';
        } elseif($type == 'array'){
            $type = 'hashMap';
        }

        if (!isset($this->types[$type])) {
            $this->types[$type] = $this->getDefaultType($type);
        }

        return $this->types[$type];
    }
}