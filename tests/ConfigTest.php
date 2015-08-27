<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\Yaml\Yaml;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $_config;
    
    public function setUp()
    {
        $configFile = __DIR__ . '/' . \Sokil\Mongo\Migrator\Console\Command::CONFIG_FILENAME . '.yaml';
        $this->_config = new Config(Yaml::parse($configFile));
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

    public function testGetConnectOptions()
    {
        $this->assertEquals(array('replicaSet' => 'testrs'), $this->_config->getConnectOptions('development'));
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