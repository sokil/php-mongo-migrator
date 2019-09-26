<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\Yaml\Yaml;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    
    public function setUp()
    {
        $configFile = __DIR__ . '/' . ManagerBuilder::DEFAULT_CONFIG_FILENAME . '.yaml';
        $this->config = new Config(Yaml::parse(file_get_contents($configFile)));
    }
    
    public function testGet()
    {
        $this->assertEquals('migrations', $this->config->get('path.migrations'));
    }

    public function testGetEnvVar()
    {
        putenv('MONGO_TEST_SOME_ENV_VAR=42');

        // get scalar value
        $this->assertEquals('42', $this->config->get('environments.development.env_var'));

        // get config section
        $configSection = $this->config->get('environments.development');
        $this->assertEquals('42', $configSection['env_var']);
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
        $this->assertEquals(array('replicaSet' => 'testrs'), $this->config->getConnectOptions('development-replicaset'));
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