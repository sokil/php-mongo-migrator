<?php

namespace Sokil\Mongo\Migrator;

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
        $configFile = __DIR__ . '/' . \Sokil\Mongo\Migrator\Console\Command::CONFIG_FILENAME;
        $config = new Config($configFile);
        
        $this->_manager = new ManagerMock($config);
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
        
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201024', 'staging'));
        $this->assertTrue($this->_manager->isRevisionApplied('20140531201019', 'staging'));
        
        $this->assertFalse($this->_manager->isRevisionApplied('20140531201027', 'staging'));
    }
}