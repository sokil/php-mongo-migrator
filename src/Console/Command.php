<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\Config;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    private $_config;
    
    const CONFIG_FILENAME = 'mongo-migrator.yaml';
    
    /**
     * 
     * @return \Sokil\Mongo\Migrator\Config
     */
    protected function getConfig()
    {
        if(!$this->_config) {
            $this->_config = new Config($this->getConfigPath());
        }
        
        return $this->_config;
    }
    
    public function getConfigPath()
    {
        return getcwd() . '/' . self::CONFIG_FILENAME;
    }
}