<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\Yaml\Yaml;

class ManagerMock extends \Sokil\Mongo\Migrator\Manager
{
    public function resetCollection($environment)
    {
        $this->getLogCollection($environment)->delete();
    }
}

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    private $_manager;
    
    public function setUp()
    {
        $configFile = __DIR__ . '/' . \Sokil\Mongo\Migrator\Console\Command::CONFIG_FILENAME . '.yaml';
        try {
            $config = new Config(Yaml::parse($configFile));
        } catch (\Exception $ex) {
            var_export($ex);

        }
        
        $this->_manager = new ManagerMock($config);
        
        $this->_manager->resetCollection('staging');
    }
    
    public function tearDown()
    {
        $this->_manager->resetCollection('staging');
    }
    
    public function testGetAvailableRevisions()
    {
        $availableRevisions = $this->_manager->getAvailableRevisions();
        
        $this->assertInternalType('array', $availableRevisions);
        
        $revision = current($availableRevisions);
        $this->assertInstanceOf('\Sokil\Mongo\Migrator\Revision', $revision);
        
        $this->assertEquals('20140531201029', $revision->getId());
        $this->assertEquals('Test5', $revision->getName());
        $this->assertEquals('20140531201029_Test5.php', $revision->getFilename());
    }

    public function testGetClient()
    {
        $reflectionClass = new \ReflectionClass($this->_manager);
        $method = $reflectionClass->getMethod('getClient');
        $method->setAccessible(true);

        $devClient = $method->invoke($this->_manager, 'development');
        $this->assertInstanceof('\Sokil\Mongo\Client', $devClient);
        $this->assertEquals('test', $devClient->getCurrentDatabaseName());

        $stagingClient = $method->invoke($this->_manager, 'staging');
        $this->assertInstanceof('\Sokil\Mongo\Client', $stagingClient);
        $this->assertEquals('staging_db', $stagingClient->getCurrentDatabaseName());
    }
    
    public function testMigrate()
    {
        $this->_manager->resetCollection('staging');
        
        $this->_manager->migrate('20140531201024', 'staging');
        
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201024', 'staging'));
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201019', 'staging'));
        
        $this->assertFalse($this->_manager->isRevisionApplied('20140531201027', 'staging'));
    }
    
    public function testRollback()
    {
        $this->_manager->migrate(null, 'staging');
        
        $this->_manager->rollback('20140531201024', 'staging');
        
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201019', 'staging'));
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201024', 'staging'));
        
        $this->assertFalse($this->_manager->isRevisionApplied('20140531201025', 'staging'));
        $this->assertFalse($this->_manager->isRevisionApplied('20140531201027', 'staging'));
    }
}