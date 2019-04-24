<?php

namespace j\db\profiler;

use j\debug\Debug;
use j\log\LogAwareInterface;
use j\log\TraitLog;

/**
 * Class Profiler
 * @package j\db\profiler
 */
class Profiler implements InterfaceProfiler, DelayDisposeInterface, LogAwareInterface{

    use TraitLog;

    public $trace = false;

    /**
     * @var self
     */
    private static $instance = null;


    /**
     * @return Profiler
     */
    static public function getInstance(){
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @var array
     */
    protected $profiles = array();

    /**
     * @var int
     */
    protected $currentIndex = 0;

    /**
     * @var int
     */
    protected $maxCounter = 1000;

    /**
     * @param int $maxCounter
     */
    public function setMaxCounter(int $maxCounter){
        $this->maxCounter = $maxCounter;
    }

    /**
     * @param string $target
     * @return Profiler
     */
    public function profilerStart($target) {
        if(count($this->profiles) > $this->maxCounter){
            $this->flush();
            $this->profiles = [];
        }

        $profileInformation = array(
            'sql' => $target,
            'parameters' => null,
            'start' => microtime(true),
            'end' => null,
            'elapse' => null
            );
        $this->profiles[$this->currentIndex] = $profileInformation;
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function profilerFinish() {
        if (!isset($this->profiles[$this->currentIndex])) {
            throw new \Exception('A profile must be started before ' . __FUNCTION__ . ' can be called.');
        }
        $current = &$this->profiles[$this->currentIndex];
        $current['end'] = microtime(true);
        $current['elapse'] = $current['end'] - $current['start'];
        if($this->trace){
            $current['backtrace'] = Debug::trace(10);
        }
        $this->currentIndex++;
        return $this;
    }

    public function flush($callback = null){
        if(is_callable($callback)){
            $callback($this->profiles);
        }
        $this->log($this->getLogContent(), 'debug');
        $this->profiles = [];
    }

    protected function getLogContent(){
        $content = "\n";
        $content .= (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'cli') . "\n";
        foreach($this->profiles as $key => $row) {
            $content .= $row['elapse']  . "\t" . $key . "\t" . $row['sql'] . "\n";
            if(isset($row['backtrace'])){
                $content .= $row['backtrace'];
            }
            $content .= "\n";
        }
        return $content;
    }

    /**
     * @return array
     */
    public function getIteration(){
        return $this->profiles;
    }
}
