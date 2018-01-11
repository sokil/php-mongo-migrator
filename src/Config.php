<?php

namespace Sokil\Mongo\Migrator;

class Config
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function __get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }
    
    public function get($name)
    {
        if (false === strpos($name, '.')) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        }

        $value = $this->config;
        foreach (explode('.', $name) as $field) {
            if (!isset($value[$field])) {
                return null;
            }

            $value = $value[$field];
        }

        return $value;
    }
    
    public function getMigrationsDir()
    {
        return rtrim($this->config['path']['migrations'], '/');
    }
    
    public function getDefaultEnvironment()
    {
        return $this->config['default_environment'];
    }
    
    public function getDefaultDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['default_database'];
    }
    
    public function getDsn($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['dsn'];
    }
    
    public function getConnectOptions($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }

        return isset($this->config['environments'][$environment]['connectOptions'])
            ? $this->config['environments'][$environment]['connectOptions']
            : array();
    }

    public function getLogDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        $log = $this->config['environments'][$environment]['log_database'];
        
        if ($log[0] === '%' && $log[strlen($log) - 1] === '%') {
            $param = str_replace('%', '', $log);

            return getenv($param);
        }

        return $log;
    }
    
    public function getLogCollectionName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['log_collection'];
    }
}
