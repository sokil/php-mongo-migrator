<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Sokil\Mongo\Client;
use Sokil\Mongo\Migrator\Event\ApplyRevisionEvent;

class Manager
{
    /**
     *
     * @var \Sokil\Mongo\Migrator\Config
     */
    private $config;
    
    private $rootDir;
    
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $client;
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $logCollection;
    
    private $appliedRevisions = array();
    
    /**
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;
    
    public function __construct(Config $config, $rootDir)
    {
        $this->config = $config;
        $this->rootDir = $rootDir;
        $this->eventDispatcher = new EventDispatcher;
    }
    
    /**
     * @param string $environment
     * @return \Sokil\Mongo\Client
     */
    private function getClient($environment)
    {
        if (empty($this->client[$environment])) {
            $this->client[$environment] = new Client(
                $this->config->getDsn($environment),
                $this->config->getConnectOptions($environment)
            );
            
            $this->client[$environment]->useDatabase($this->config->getDefaultDatabaseName($environment));
        }
        
        return $this->client[$environment];
    }
    
    public function getMigrationsDir()
    {
        $migrationsDir = $this->config->getMigrationsDir();
        if($migrationsDir[0] === '/') {
            return $migrationsDir;
        }
        
        return $this->rootDir . '/' . rtrim($migrationsDir, '/');
    }
    
    public function getAvailableRevisions()
    {
        $list = array();
        foreach(new \DirectoryIterator($this->getMigrationsDir()) as $file) {
            if(!$file->isFile()) {
                continue;
            }
            
            list($id, $className) = explode('_', $file->getBasename('.php'));
            
            $revision = new Revision();
            $revision
                ->setId($id)
                ->setName($className)
                ->setFilename($file->getFilename());
            
            $list[$id] = $revision;
            
            krsort($list);
        }
        
        return $list;
    }
    
    protected function getLogCollection($environment)
    {
        if($this->logCollection) {
            return $this->logCollection;
        }
        
        $databaseName = $this->config->getLogDatabaseName($environment);
        $collectionName = $this->config->getLogCollectionName($environment);
        
        $this->logCollection = $this
            ->getClient($environment)
            ->getDatabase($databaseName)
            ->getCollection($collectionName);
        
        return $this->logCollection;
    }
    
    protected function logUp($revision, $environment)
    {
        $this->getLogCollection($environment)->createDocument(array(
            'revision'  => $revision,
            'date'      => new \MongoDate, 
        ))->save();
        
        return $this;
    }
    
    protected function logDown($revision, $environment)
    {
        $collection = $this->getLogCollection($environment);
        $collection->deleteDocuments($collection->expression()->where('revision', $revision));
        
        return $this;
    }
    
    public function getAppliedRevisions($environment)
    {
        if(isset($this->appliedRevisions[$environment])) {
            return $this->appliedRevisions[$environment];
        }
        
        $documents = array_values($this
            ->getLogCollection($environment)
            ->find()
            ->sort(array('revision' => 1))
            ->map(function($document) {
                return $document->revision;
            }));
            
        if(!$documents) {
            return array();
        }
        
        $this->appliedRevisions[$environment] = $documents;
            
        return $this->appliedRevisions[$environment];
    }
    
    public function isRevisionApplied($revision, $environment)
    {
        return in_array($revision, $this->getAppliedRevisions($environment));
    }
    
    protected function getLatestAppliedRevisionId($environment)
    {
        $revisions = $this->getAppliedRevisions($environment);
        return end($revisions);
    }
    
    protected function executeMigration($targetRevision, $environment, $direction)
    {
        $this->eventDispatcher->dispatch('start');
        
        // get last applied migration
        $latestRevisionId = $this->getLatestAppliedRevisionId($environment);
        
        // get list of migrations
        $availableRevisions = $this->getAvailableRevisions();
        
        // execute
        if($direction === 1) {
            $this->eventDispatcher->dispatch('before_migrate');
            
            ksort($availableRevisions);

            foreach($availableRevisions as $revision) {
                
                if($revision->getId() <= $latestRevisionId) {
                    continue;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->eventDispatcher->dispatch('before_migrate_revision', $event);

                require_once $this->getMigrationsDir() . '/' . $revision->getFilename();
                $className = $revision->getName();
                
                $migration = new $className(
                    $this->getClient($environment)
                );

                $migration->setEnvironment($environment);

                $migration->up();
                
                $this->logUp($revision->getId(), $environment);
                
                $this->eventDispatcher->dispatch('migrate_revision', $event);
                
                if($targetRevision && in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
            }
            
            $this->eventDispatcher->dispatch('migrate');
        } else {
            
            $this->eventDispatcher->dispatch('before_rollback');
            
            // check if nothing to revert
            if(!$latestRevisionId) {
                return;
            }
            
            krsort($availableRevisions);

            foreach($availableRevisions as $revision) {

                if($revision->getId() > $latestRevisionId) {
                    continue;
                }
                
                if($targetRevision && in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->eventDispatcher->dispatch('before_rollback_revision', $event);

                require_once $this->getMigrationsDir() . '/' . $revision->getFilename();
                $className = $revision->getName();
                
                $migration = new $className($this->getClient($environment));
                $migration->down();
                
                $this->logDown($revision->getId(), $environment);
                
                $this->eventDispatcher->dispatch('rollback_revision', $event);
                
                if(!$targetRevision) {
                    break;
                }
            }
            
            $this->eventDispatcher->dispatch('rollback');
        }
        
        $this->eventDispatcher->dispatch('stop');
        
        // clear cached applied revisions
        unset($this->appliedRevisions[$environment]);
    }
    
    public function migrate($revision, $environment)
    {
        $this->executeMigration($revision, $environment, 1);
        return $this;
    }
    
    public function rollback($revision, $environment)
    {
        $this->executeMigration($revision, $environment, -1);
        return $this;
    }

    public function onStart($listener)
    {
        $this->eventDispatcher->addListener('start', $listener);
        return $this;
    }
    
    public function onBeforeMigrate($listener)
    {
        $this->eventDispatcher->addListener('before_migrate', $listener);
        return $this;
    }
    
    public function onBeforeMigrateRevision($listener)
    {
        $this->eventDispatcher->addListener('before_migrate_revision', $listener);
        return $this;
    }
    
    public function onMigrateRevision($listener)
    {
        $this->eventDispatcher->addListener('migrate_revision', $listener);
        return $this;
    }
    
    public function onMigrate($listener)
    {
        $this->eventDispatcher->addListener('migrate', $listener);
        return $this;
    }
    
    public function onBeforeRollback($listener)
    {
        $this->eventDispatcher->addListener('before_rollback', $listener);
        return $this;
    }
    
    public function onBeforeRollbackRevision($listener)
    {
        $this->eventDispatcher->addListener('before_rollback_revision', $listener);
        return $this;
    }
    
    public function onRollbackRevision($listener)
    {
        $this->eventDispatcher->addListener('rollback_revision', $listener);
        return $this;
    }
    
    public function onRollback($listener)
    {
        $this->eventDispatcher->addListener('rollback', $listener);
        return $this;
    }
    
    public function onStop($listener)
    {
        $this->eventDispatcher->addListener('stop', $listener);
        return $this;
    }
}
