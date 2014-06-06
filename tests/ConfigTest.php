<?php

namespace Sokil\Mongo\Migrator;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $_config;
    
    public function setUp()
    {
        $configFile = __DIR__ . '/' . \Sokil\Mongo\Migrator\Console\Command::CONFIG_FILENAME;
        $this->_config = new Config($configFile);
    }
    
    public function testGet()
    {
        $this->assertEquals('migrations', $this->_config->get('path.migrations'));
    }
    
    public function testGetDefaultDatabaseName()
    {        
        $this->assertEquals('test', $this->_config->getDefaultDatabaseName('development'));
    }
    
    public function testGetDsn()
    {        
        $this->assertEquals('mongodb://localhost', $this->_config->getDsn('development'));
    }
    
    public function testGetLogDatabaseName()
    {        
        $this->assertEquals('test', $this->_config->getLogDatabaseName('development'));
    }
    
    public function testGetLogCollectionName()
    {        
        $this->assertEquals('migrations', $this->_config->getLogCollectionName('development'));
    }
}