<?php

namespace Sokil\Mongo\Migrator;

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
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Client
     */
    private function getClient($environment)
    {
        if(empty($this->_client[$environment])) {
            $this->_client[$environment] = new \Sokil\Mongo\Client($this->_config->getDsn($environment));
        }
        
        return $this->_client[$environment];
    }
    
    private function getAvailableMigrations()
    {
        $list = array();
        foreach(new \DirectoryIterator($this->_config->getMigrationsDir()) as $file) {
            if(!$file->isFile()) {
                continue;
            }
            
            list($revision, $className) = explode('_', $file->getBasename('.php'));
            
            $list[$revision] = array(
                'revision'  => $revision,
                'className' => $className,
                'fileName'  => $file->getFilename(),
            );
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
        return array_values($this
            ->getLogCollection($environment)
            ->find()
            ->sort(array('revision' => 1))
            ->map(function($document) {
                return $document->revision;
            }));
    }
    
    private function getLatestAppliedRevision($environment)
    {
        return end($this->getAppliedRevisions($environment));
    }
    
    private function executeMigration($revision, $environment, $direction)
    {        
        // get last applied migration
        $latestRevision = $this->getLatestAppliedRevision($environment);
        
        // get list of migrations
        $availableMigrations = $this->getAvailableMigrations();
        
        // execute
        if($direction === 1) {
            ksort($availableMigrations);

            foreach($availableMigrations as $migrationMeta) {
                if($migrationMeta['revision'] <= $latestRevision) {
                    continue;
                }
                

                require_once $this->_config->getMigrationsDir() . '/' . $migrationMeta['fileName'];
                $migration = new $migrationMeta['className'];
                $migration->up();
                
                $this->logUp($migrationMeta['revision'], $environment);
                
                if($revision && in_array($revision, array($migrationMeta['revision'], $migrationMeta['className']))) {
                    break;
                }
            }
        } else {
            // check if nothing to revert
            if(!$latestRevision) {
                return;
            }
            
            krsort($availableMigrations);

            foreach($availableMigrations as $migrationMeta) {
                if($migrationMeta['revision'] > $latestRevision) {
                    continue;
                }

                require_once $this->_config->getMigrationsDir() . '/' . $migrationMeta['fileName'];
                $migration = new $migrationMeta['className'];
                $migration->down();
                
                $this->logDown($migrationMeta['revision'], $environment);
                
                if(!$revision || in_array($revision, array($migrationMeta['revision'], $migrationMeta['className']))) {
                    break;
                }
            }
        }
        
        
    }
    
    public function migrate($revision, $environment)
    {
        $this->executeMigration($revision, $environment, 1);
    }
    
    public function rollback($revision, $environment)
    {
        $this->executeMigration($revision, $environment, -1);
    }
}