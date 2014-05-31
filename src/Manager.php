<?php

namespace Sokil\Mongo\Migrator;

class Manager
{
    /**
     *
     * @var \Sokil\Mongo\Migrator\Config
     */
    private $_config;
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }
    
    public function migrate($revision, $environment)
    {
        
    }
    
    public function rollback($revision, $environment)
    {
        
    }
}