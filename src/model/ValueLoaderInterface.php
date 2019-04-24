<?php

namespace j\model;

/**
 * Interface SetValueLoaderInterface
 * @package j\db\setDecorator
 */
interface ValueLoaderInterface {
    /**
     * @param $key
     * @param Model|array $context
     * @return bool
     */
    function isCanLoad($context, $key);

    /**
     * @param string $key
     * @param Model|array $context
     * @return mixed
     */
    function loadValue($context, $key);


    /**
     * @return array
     */
    function getKeys();
}