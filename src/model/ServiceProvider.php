<?php

namespace j\model;

use j\base\Config;
use j\base\SingletonInterface;
use j\base\SingletonTrait;
use j\db\Dao;
use j\di\ServiceProviderInterface;
use j\di\Container;

/**
 * Class ServiceProvider
 * @package j\model
 */
class ServiceProvider implements ServiceProviderInterface, SingletonInterface  {

    use SingletonTrait;

    /**
     * @var Container
     */
    protected $di;
    protected $options = [
        'daoPre' =>  'model\\',
        'daoSuf' =>  'Dao',
        'modelPre' =>  'model\\',
        'modelSuf' =>  '',
        'servicePre' =>  'model\\',
        'serviceSuf' =>  'Service',
        'serviceScene' =>  Service::SCENE_NORMAL,
        ];

    /**
     * @var string
     */
    protected $daoPrefix = 'model\\';
    protected $daoSuffix = 'Dao';
    protected $modelPrefix = 'model\\';
    protected $modelSuffix = '';

    protected function initOptions(){
        $container = $this->di;
        if(!$container->has('config')){
            return;
        }

        /** @var Config $config */
        $config = $container->get('config');
        if(!$config->has('model')){
            return;
        }

        $setting = $config->get('model');
        $this->options = array_merge($this->options, $setting);
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register($container) {
        $this->di = $container;
        $this->initOptions();

        if(!$container->has('dao')) {
            $container->set('dao', function($di, $name){
                return $this->getObject($name, 'dao');
            }, true);
        }

        if(!$container->has('model')) {
            $container->set('model', function($di, $name){
                return $this->getObject($name, 'model');
            }, true);
        }

        if(!$container->has('service')) {
            $container->set('service', function($di, $name){
                /** @var Service $service */
                $service = $this->getObject($name, 'service');
                if(method_exists($service, 'setScene')){
                    $service->setScene($this->options['serviceScene']);
                }
                return $service;
            }, true);
        }

        if(!$container->has('repository')) {
            $container->set('repository', function($di, $name){
                return $this->getObject($name, 'dao');
            }, true);
        }
    }

    protected function getObject($name, $type){
        $name = $this->normalizesName($name);
        $pre = $type . 'Pre';
        $suf = $type . 'Suf';
        $class = $this->options[$pre] . $name . $this->options[$suf];
        return $this->di->make($class);
    }

    /**
     * @param $name
     * @return string
     */
    protected function normalizesName($name){
        $name = str_replace('.', '\\', $name);
        if(strpos($name, '\\') == false){
            $name = ucfirst($name);
        } else {
            $names = explode('\\', $name);
            $last = count($names) - 1;
            $names[$last] = ucfirst($names[$last]);
            $name = implode('\\', $names);
        }
        return $name;
    }
}