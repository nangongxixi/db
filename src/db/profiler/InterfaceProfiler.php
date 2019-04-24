<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-6-24
 * Time: 下午6:10
 * To change this template use File | Settings | File Templates.
 */
namespace j\db\profiler;

/**
 * Interface InterfaceProfiler
 * @package j\db\profiler
 */
interface InterfaceProfiler{
    /**
     * @param $target
     * @return mixed
     */
    public function profilerStart($target);

    /**
     * @return mixed
     */
    public function profilerFinish();
}