<?php

namespace Sokil\Mongo\Migrator;

use Sokil\Mongo\Client;

abstract class AbstractMigration
{
    /**
     *
     * @var Client
     */
    private $client;

    private $environment;
    
    public function __construct(
        Client $client
    ) {
        $this->client = $client;

        $this->init();
    }

    /**
     * Do some job before migrating up or down
     */
    protected function init() {}
    
    /**
     * 
     * @param string $name
     * @return \Sokil\Mongo\Database
     */
    protected function getDatabase($name = null)
    {
        return $this->client->getDatabase($name);
    }
    
    /**
     * 
     * @param string $name
     * @return \Sokil\Mongo\Collection
     */
    protected function getCollection($name)
    {
        return $this->client->getCollection($name);
    }

    /**
     * Define environment of migration to run in
     *
     * @param $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * Get environment of running migration
     *
     * @return mixed
     */
    protected function getEnvironment()
    {
        return $this->environment;
    }
    
    public function up() {}
    
    public function down() {}
}