<?php

namespace j\model;

/**
 * Interface EnableValueManger
 * @package common
 */
interface ValueMangerAwareInterface {
    /**
     * @param ValueManager $manger
     */
    function setValueManger($manger);
};
