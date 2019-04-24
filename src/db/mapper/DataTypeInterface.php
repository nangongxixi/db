<?php

namespace j\db\mapper;

/**
 * Interface DataTypeInterface
 * @package j\db\mapper
 */
interface DataTypeInterface {
    /**
     * @param $value
     * @param array $attrs
     * @return mixed
     */
    public function store($value, array $attrs);

    /**
     * @param $value
     * @param array $attrs
     * @return mixed
     */
    public function restore($value, array $attrs);

}