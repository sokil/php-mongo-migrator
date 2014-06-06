<?php

namespace Sokil\Mongo\Migrator;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Sokil\Mongo\Migrator\Event\ApplyRevisionEvent;

class Manager
{
    /**
     *
     * @var \Sokil\Mongo\Migrator\Config
     */
    private $_config;
    
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $_client;
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $_logCollection;
    
    private $_appliedRevisions;
    
    /**
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private $_eventDispatcher;
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
        
        $this->_eventDispatcher = new EventDispatcher;
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Client
     */
    private function getClient($environment)
    {
        if(empty($this->_client[$environment])) {
            $this->_client[$environment] = new \Sokil\Mongo\Client($this->_config->getDsn($environment));
            
            $this->_client[$environment]->useDatabase($this->_config->getDefaultDatabaseName());
        }
        
        return $this->_client[$environment];
    }
    
    public function getAvailableRevisions()
    {
        $list = array();
        foreach(new \DirectoryIterator($this->_config->getMigrationsDir()) as $file) {
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
    
    private function getLogCollection($environment)
    {
        if($this->_logCollection) {
            return $this->_logCollection;
        }
        
        $databaseName = $this->_config->getLogDatabaseName($environment);
        $collectionName = $this->_config->getLogCollectionName($environment);
        
        $this->_logCollection = $this
            ->getClient($environment)
            ->getDatabase($databaseName)
            ->getCollection($collectionName);
        
        return $this->_logCollection;
    }
    
    private function logUp($revision, $environment)
    {
        $this->getLogCollection($environment)->createDocument(array(
            'revision'  => $revision,
            'date'      => new \MongoDate, 
        ))->save();
        
        return $this;
    }
    
    private function logDown($revision, $environment)
    {
        $collection = $this->getLogCollection($environment);
        $collection->deleteDocuments($collection->expression()->where('revision', $revision));
        
        return $this;
    }
    
    private function getAppliedRevisions($environment)
    {
        if(isset($this->_appliedRevisions[$environment])) {
            return $this->_appliedRevisions[$environment];
        }
        
        $this->_appliedRevisions[$environment] = array_values($this
            ->getLogCollection($environment)
            ->find()
            ->sort(array('revision' => 1))
            ->map(function($document) {
                return $document->revision;
            }));
            
        return $this->_appliedRevisions[$environment];
    }
    
    public function isRevisionApplied($revision, $environment)
    {
        return in_array($revision, $this->getAppliedRevisions($environment));
    }
    
    private function getLatestAppliedRevisionId($environment)
    {
        return end($this->getAppliedRevisions($environment));
    }
    
    private function executeMigration($targetRevision, $environment, $direction)
    {
        $this->_eventDispatcher->dispatch('start');
        
        // get last applied migration
        $latestRevisionId = $this->getLatestAppliedRevisionId($environment);
        
        // get list of migrations
        $availableRevisions = $this->getAvailableRevisions();
        
        // execute
        if($direction === 1) {
            $this->_eventDispatcher->dispatch('before_migrate');
            
            ksort($availableRevisions);

            foreach($availableRevisions as $revision) {
                
                if($revision->getId() <= $latestRevisionId) {
                    continue;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->_eventDispatcher->dispatch('before_migrate_revision', $event);

                require_once $this->_config->getMigrationsDir() . '/' . $revision->getFilename();
                $className = $revision->getName();
                
                $migration = new $className($this->getClient($environment));
                $migration->up();
                
                $this->logUp($revision->getId(), $environment);
                
                $this->_eventDispatcher->dispatch('migrate_revision', $event);
                
                if($targetRevision && in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
            }
            
            $this->_eventDispatcher->dispatch('migrate');
        } else {
            
            $this->_eventDispatcher->dispatch('before_rollback');
            
            // check if nothing to revert
            if(!$latestRevisionId) {
                return;
            }
            
            krsort($availableRevisions);

            foreach($availableRevisions as $revision) {

                if($revision->getId() > $latestRevisionId) {
                    continue;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->_eventDispatcher->dispatch('before_rollback_revision', $event);

                require_once $this->_config->getMigrationsDir() . '/' . $revision->getFilename();
                $className = $revision->getName();
                
                $migration = new $className($this->getClient($environment));
                $migration->down();
                
                $this->logDown($revision->getId(), $environment);
                
                $this->_eventDispatcher->dispatch('rollback_revision', $event);
                
                if(!$targetRevision || in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
            }
            
            $this->_eventDispatcher->dispatch('rollback');
        }
        
        $this->_eventDispatcher->dispatch('stop');
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
        $this->_eventDispatcher->addListener('start', $listener);
        return $this;
    }
    
    public function onBeforeMigrate($listener)
    {
        $this->_eventDispatcher->addListener('before_migrate', $listener);
        return $this;
    }
    
    public function onBeforeMigrateRevision($listener)
    {
        $this->_eventDispatcher->addListener('before_migrate_revision', $listener);
        return $this;
    }
    
    public function onMigrateRevision($listener)
    {
        $this->_eventDispatcher->addListener('migrate_revision', $listener);
        return $this;
    }
    
    public function onMigrate($listener)
    {
        $this->_eventDispatcher->addListener('migrate', $listener);
        return $this;
    }
    
    public function onBeforeRollback($listener)
    {
        $this->_eventDispatcher->addListener('before_rollback', $listener);
        return $this;
    }
    
    public function onBeforeRollbackRevision($listener)
    {
        $this->_eventDispatcher->addListener('before_rollback_revision', $listener);
        return $this;
    }
    
    public function onRollbackRevision($listener)
    {
        $this->_eventDispatcher->addListener('rollback_revision', $listener);
        return $this;
    }
    
    public function onRollback($listener)
    {
        $this->_eventDispatcher->addListener('rollback', $listener);
        return $this;
    }
    
    public function onStop($listener)
    {
        $this->_eventDispatcher->addListener('stop', $listener);
        return $this;
    }
}