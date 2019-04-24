<?php

namespace j\db;

use j\base\Config;
use j\db\profiler\DelayDisposeInterface;
use j\db\profiler\Profiler;
use j\db\profiler\Simple;
use j\di\ServiceProviderInterface;
use j\di\Container;
use j\log\File;
use j\log\LogAwareInterface;

/**
 * Class ServiceProvider
 * @package j\db
 */
class ServiceProvider implements ServiceProviderInterface {
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register($container) {
        $this->configLogger($container);

        if(!$container->has('dbTable')) {
            $container->set('dbTable', array($this, 'dbTable'), true);
        }

        if(!$container->has('dbConn')) {
            $container->set('dbConn', array($this, 'dbConn'));
        }

        if(!$container->has('dbAdapter')) {
            $container->set('dbAdapter', array($this, 'dbAdapter'));
        }
    }

    /**
     * @param Container $container
     * @param $name
     * @return Table
     * @throws \Exception
     */
    public function dbTable(Container $container, $name){
        if($container->has('config')) {
            /** @var Config $config */
            $config = $container->get('config');
            $prefix = $config->get('db.prefix');
            if($prefix){
                if(!strpos($name, '.')){
                    $name = $prefix . $name;
                }
            }
        }

        $db = $container->get('dbAdapter');
        return Table::factory($name, $db);
    }

    /**
     * @param Container $container
     * @return driver\mysql\Mysql|driver\mysqli\Mysql|array
     * @throws \Exception
     */
    public function dbConn(Container $container) {
        if(!$container->has('config')) {
            throw new \Exception("Not found config");
        }

        /** @var Config $config */
        $config = $container->get('config');
        $db = $config->get('db.conn', []);

        if(!$db){
            throw new \Exception("Not found config for db");
        }

        $conn = [];
        if(isset($db['master'])){
            $conn['w'] = Adapter::createDbConn($db['master']);
        }

        if(isset($db['slave'])){
            $conn['r'] = Adapter::createDbConn($db['slave']);
        }

        if(!$conn) {
            $conn = Adapter::createDbConn($db);
        }

        // todo maybe return array
        return $conn;
    }

    /**
     * @param Container $container
     * @return Adapter
     * @throws \Exception
     */
    public function dbAdapter(Container $container) {
        $dbProfiler = null;
        if($container->has('dbProfiler')) {
            $dbProfiler = $container->get('dbProfiler');
        }

        return new Adapter($container->get('dbConn'), $dbProfiler);
    }

    /**
     * @param Container $container
     */
    public function configLogger(Container $container){
        $config = $container->get('config');
        if(!$config->get('db.log.enable')){
            return;
        }

        if(!$container->has('dbProfiler')) {
            $container->set('dbProfiler', Profiler::class);
        }

        $container->extend('dbProfiler', function($profiler) use($config){
            /** @var Profiler $profiler */
            if($config->get('db.log.trace')){
                $profiler->trace = 1;
            }

            if(!($profiler instanceof LogAwareInterface)){
                return;
            }

            $logger = $config->get('db.log.logger');
            if($logger && is_string($logger)){
                $logger = new File($logger);
            } elseif(!is_object($logger)){
                return;
            }

            $profiler->setLogger($logger);

            $max = $config->get('db.log.maxFlush');
            if($max > 0 && method_exists($profiler, 'setMaxCounter')){
                $profiler->setMaxCounter($max);
            }

            // log content to file
            if($profiler instanceof DelayDisposeInterface) {
                register_shutdown_function(function() use($profiler){
                    $profiler->flush();
                });
            }
        });
    }
}
