<?php

namespace Sokil\Mongo\Migrator;

class Config
{
    private $_config;

    public function __construct(array $config)
    {        
        $this->_config = $config;
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
        return rtrim($this->_config['path']['migrations'], '/');
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
    
    public function getOptions($environment = null)
    {
        if(!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return isset($this->_config['environments'][$environment]['options']) ? $this->_config['environments'][$environment]['options'] : [];
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
