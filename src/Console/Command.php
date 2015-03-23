<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\Config;
use Sokil\Mongo\Migrator\Console\Exception\ConfigurationNotFound;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    private $_config;
    
    /**
     *
     * @var \Sokil\Mongo\Migrator\Manager
     */
    private $_manager;
    
    const CONFIG_FILENAME = 'mongo-migrator';
    
    /**
     * 
     * @return \Sokil\Mongo\Migrator\Config
     */
    protected function getConfig()
    {
        if(!$this->_config) {
            $this->_config = new Config($this->readConfig());
        }
        
        return $this->_config;
    }
    
    private function readConfig()
    {
        $filename = $this->getProjectRoot() . '/' . self::CONFIG_FILENAME;

        $yamlFilename = $filename . '.yaml';
        if(file_exists($yamlFilename)) {
            return Yaml::parse($yamlFilename);
        }

        $phpFilename = $filename . '.php';
        if(file_exists($phpFilename)) {
            return require($phpFilename);
        }
        
        throw new ConfigurationNotFound('Config not found');
    }
    
    public function isProjectInitialisd()
    {
        try {
            $config = $this->getConfig();
            return (bool) $config;
        } catch (ConfigurationNotFound $e) {
            return false;
        }
    }
    
    public function getProjectRoot()
    {
        return getcwd();
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Migrator\Manager
     */
    public function getManager()
    {
        if(!$this->_manager) {
            $this->_manager = new \Sokil\Mongo\Migrator\Manager($this->getConfig());
        }
        
        return $this->_manager;
    }
}