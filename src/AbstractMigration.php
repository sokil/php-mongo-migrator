<?php

namespace Sokil\Mongo\Migrator;

abstract class AbstractMigration
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $_client;
    
    public function setClient(\Sokil\Mongo\Client $client)
    {
        $this->_client = $client;
        return $this;
    }
    
    /**
     * 
     * @param string $name
     * @return \Sokil\Mongo\Database
     */
    protected function getDatabase($name = null)
    {
        return $this->_client->getDatabase($name);
    }
    
    /**
     * 
     * @param string $name
     * @return \Sokil\Mongo\Collection
     */
    protected function getCollection($name)
    {
        return $this->_client->getCollection($name);
    }
    
    public function up() {}
    
    public function down() {}
}