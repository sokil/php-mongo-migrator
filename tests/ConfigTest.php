<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\Yaml\Yaml;
use Sokil\Mongo\Migrator\Console\Command;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    
    public function setUp()
    {
        $configFile = __DIR__ . '/' . Command::CONFIG_FILENAME . '.yaml';
        $this->config = new Config(Yaml::parse(file_get_contents($configFile)));
    }
    
    public function testGet()
    {
        $this->assertEquals('migrations', $this->config->get('path.migrations'));
    }
    
    public function testGetDefaultDatabaseName()
    {        
        $this->assertEquals('test', $this->config->getDefaultDatabaseName('development'));
    }
    
    public function testGetDsn()
    {        
        $this->assertEquals('mongodb://localhost', $this->config->getDsn('development'));
    }

    public function testGetConnectOptions()
    {
        $this->assertEquals(array('replicaSet' => 'testrs'), $this->config->getConnectOptions('development'));
        $this->assertEquals(array(), $this->config->getConnectOptions('staging'));
    }
    
    public function testGetLogDatabaseName()
    {        
        $this->assertEquals('test', $this->config->getLogDatabaseName('development'));
    }
    
    public function testGetLogCollectionName()
    {        
        $this->assertEquals('migrations', $this->config->getLogCollectionName('development'));
    }
}