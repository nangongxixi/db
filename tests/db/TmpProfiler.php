<?php

namespace j\db;

use j\db\profiler\InterfaceProfiler;

/**
 * Class TmpProfiler
 * @package j\db
 */
class TmpProfiler implements InterfaceProfiler{

    public $count = 0;

    public function profilerStart($target){
        $this->count++;
        // TODO: Implement profilerStart() method.
    }

    public function profilerFinish(){
        // TODO: Implement profilerFinish() method.
    }
}