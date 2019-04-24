<?php

namespace j\db\mapper;

/**
 * Class DataMapperInterface
 * @package j\db\mapper
 */
interface DataMapperInterface {

    /**
     * @param $data
     * @return mixed
     */
    function store($data);

    /**
     * @param $data
     * @return mixed
     */
    function restore($data);

}