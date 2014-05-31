<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private $_config;
    
    private $_configFilePath;

    public function __construct($path)
    {
        $this->_configFilePath = rtrim($path, '/');
        
        $this->_config = Yaml::parse($path);
    }
    
    public function __get($name)
    {
        return isset($this->_config[$name]) ? $this->_config[$name] : null;
    }
    
    public function get($name)
    {
        if(false === strpos($name, '.')) {
            return isset($this->_config[$name]) ? $this->_config[$name] : null;
        }

        $value = $this->_config;
        foreach(explode('.', $name) as $field)
        {
            if(!isset($value[$field])) {
                return null;
            }

            $value = $value[$field];
        }

        return $value;
    }
    
    public function getMigrationsDir()
    {
        return dirname($this->_configFilePath) . '/' . trim($this->_config['path']['migrations'], '/');
    }
    
    public function getDefaultEnvironment()
    {
        return $this->_config['default_environment'];
    }
    
    public function getDefaultDatabaseName($environment = null)
    {
        if(!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->_config['environments'][$environment]['default_database'];
    }
    
    public function getDsn($environment = null)
    {
        if(!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->_config['environments'][$environment]['dsn'];
    }
    
    public function getLogDatabaseName($environment = null)
    {
        if(!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->_config['environments'][$environment]['log_database'];
    }
    
    public function getLogCollectionName($environment = null)
    {
        if(!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->_config['environments'][$environment]['log_collection'];
    }
}