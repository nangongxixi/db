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
interface DelayDisposeInterface {
    /**
     * @param null $callback
     */
    public function flush($callback = null);

    /**
     * @return array
     */
    public function getIteration();
}
