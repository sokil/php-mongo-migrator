<?php

namespace Sokil\Mongo\Migrator;

use Sokil\Mongo\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sokil\Mongo\Client;
use Sokil\Mongo\Migrator\Event\ApplyRevisionEvent;

/**
 * Migration management
 */
class Manager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $configPath;
    
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var Collection
     */
    private $logCollection;

    /**
     * @var array
     */
    private $appliedRevisions = array();
    
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Manager constructor.
     * @param Config $config
     * @param string $configPath
     * @param string $rootDir
     */
    public function __construct(Config $config, $configPath, $rootDir)
    {
        $this->config = $config;
        $this->rootDir = $rootDir;
        $this->configPath = $configPath;
        $this->eventDispatcher = new EventDispatcher;
    }
    
    /**
     * @param string $environment
     *
     * @return Client
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

    /**
     * @return string
     */
    public function getMigrationsDir()
    {
        $migrationsDir = $this->config->getMigrationsDir();
        if ($migrationsDir[0] === '/') {
            return $migrationsDir;
        }

        if (!empty($this->configPath)) {
            $pathParts = pathinfo($this->configPath);
            return $pathParts['dirname'] . '/' . rtrim($migrationsDir, '/');
        }

        return $this->rootDir . '/' . rtrim($migrationsDir, '/');
    }

    /**
     * @param int|null $limit If specified, get only last revisions
     *
     * @return Revision[]
     */
    public function getAvailableRevisions($limit = null)
    {
        if ($limit !==null && !is_integer($limit)) {
            throw new \InvalidArgumentException('Limit must be integer');
        }

        $list = [];

        foreach (new \DirectoryIterator($this->getMigrationsDir()) as $file) {
            if (!$file->isFile()) {
                continue;
            }
            
            list($id, $className) = explode('_', $file->getBasename('.php'));
            
            $revision = new Revision();
            $revision
                ->setId($id)
                ->setName($className)
                ->setFilename($file->getFilename());
            
            $list[$id] = $revision;
            
            ksort($list);
        }

        if ($limit !== null) {
            $list = array_slice($list, -$limit);
        }

        return $list;
    }

    /**
     * @param string $environment
     *
     * @return Collection
     *
     * @throws \Sokil\Mongo\Exception
     */
    protected function getLogCollection($environment)
    {
        if ($this->logCollection) {
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

    /**
     * @param string $revision
     * @param string $environment
     *
     * @return self
     *
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Exception\WriteException
     */
    protected function logUp($revision, $environment)
    {
        $this
            ->getLogCollection($environment)
            ->createDocument(array(
                'revision'  => $revision,
                'date'      => new \MongoDate,
            ))
            ->save();
        
        return $this;
    }

    /**
     * @param string $revision
     * @param string $environment
     *
     * @return self
     *
     * @throws \Sokil\Mongo\Exception
     */
    protected function logDown($revision, $environment)
    {
        $collection = $this->getLogCollection($environment);
        $collection->batchDelete($collection->expression()->where('revision', $revision));
        
        return $this;
    }

    /**
     * @param string $environment
     *
     * @return array
     *
     * @throws \Sokil\Mongo\Exception
     */
    public function getAppliedRevisions($environment)
    {
        if (isset($this->appliedRevisions[$environment])) {
            return $this->appliedRevisions[$environment];
        }
        
        $documents = array_values(
            $this
                ->getLogCollection($environment)
                ->find()
                ->sort(array('revision' => 1))
                ->map(function ($document) {
                    return $document->revision;
                })
        );
            
        if (!$documents) {
            return array();
        }
        
        $this->appliedRevisions[$environment] = $documents;
            
        return $this->appliedRevisions[$environment];
    }

    /**
     * @param string $revision
     * @param string $environment
     *
     * @return bool
     *
     * @throws \Sokil\Mongo\Exception
     */
    public function isRevisionApplied($revision, $environment)
    {
        return in_array($revision, $this->getAppliedRevisions($environment));
    }

    /**
     * @param string $environment
     *
     * @return string
     *
     * @throws \Sokil\Mongo\Exception
     */
    protected function getLatestAppliedRevisionId($environment)
    {
        $revisions = $this->getAppliedRevisions($environment);
        return end($revisions);
    }

    /**
     * @param string $targetRevision
     * @param string $environment
     * @param string $direction
     *
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Exception\WriteException
     */
    protected function executeMigration($targetRevision, $environment, $direction)
    {
        $this->eventDispatcher->dispatch('start');

        // get last applied migration
        $latestRevisionId = $this->getLatestAppliedRevisionId($environment);
        
        // get list of migrations
        $availableRevisions = $this->getAvailableRevisions();
        
        // execute
        if ($direction === 1) {
            $this->eventDispatcher->dispatch('before_migrate');
            
            ksort($availableRevisions);

            foreach ($availableRevisions as $revision) {
                if ($revision->getId() <= $latestRevisionId) {
                    continue;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->eventDispatcher->dispatch('before_migrate_revision', $event);

                $revisionPath = $this->getMigrationsDir() . '/' . $revision->getFilename();
                var_dump($revisionPath);die;
                require_once $revisionPath;

                $className = $revision->getName();
                
                $migration = new $className(
                    $this->getClient($environment)
                );

                $migration->setEnvironment($environment);

                $migration->up();
                
                $this->logUp($revision->getId(), $environment);
                
                $this->eventDispatcher->dispatch('migrate_revision', $event);
                
                if ($targetRevision && in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
            }
            
            $this->eventDispatcher->dispatch('migrate');
        } else {
            $this->eventDispatcher->dispatch('before_rollback');
            
            // check if nothing to revert
            if (!$latestRevisionId) {
                return;
            }
            
            krsort($availableRevisions);

            foreach ($availableRevisions as $revision) {
                if ($revision->getId() > $latestRevisionId) {
                    continue;
                }
                
                if ($targetRevision && in_array($targetRevision, array($revision->getId(), $revision->getName()))) {
                    break;
                }
                
                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                
                $this->eventDispatcher->dispatch('before_rollback_revision', $event);

                $revisionPath = $this->getMigrationsDir() . '/' . $revision->getFilename();
                require_once $revisionPath;

                $className = $revision->getName();
                
                $migration = new $className($this->getClient($environment));
                $migration->setEnvironment($environment);
                $migration->down();
                
                $this->logDown($revision->getId(), $environment);
                
                $this->eventDispatcher->dispatch('rollback_revision', $event);
                
                if (!$targetRevision) {
                    break;
                }
            }
            
            $this->eventDispatcher->dispatch('rollback');
        }
        
        $this->eventDispatcher->dispatch('stop');
        
        // clear cached applied revisions
        unset($this->appliedRevisions[$environment]);
    }

    /**
     * @param string $revision
     * @param string $environment
     *
     * @return self
     *
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Exception\WriteException
     */
    public function migrate($revision, $environment)
    {
        $this->executeMigration($revision, $environment, 1);

        return $this;
    }

    /**
     * @param string $revision
     * @param string $environment
     *
     * @return self
     *
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Exception\WriteException
     */
    public function rollback($revision, $environment)
    {
        $this->executeMigration($revision, $environment, -1);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onStart($listener)
    {
        $this->eventDispatcher->addListener('start', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onBeforeMigrate($listener)
    {
        $this->eventDispatcher->addListener('before_migrate', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onBeforeMigrateRevision($listener)
    {
        $this->eventDispatcher->addListener('before_migrate_revision', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onMigrateRevision($listener)
    {
        $this->eventDispatcher->addListener('migrate_revision', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
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

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onBeforeRollbackRevision($listener)
    {
        $this->eventDispatcher->addListener('before_rollback_revision', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onRollbackRevision($listener)
    {
        $this->eventDispatcher->addListener('rollback_revision', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onRollback($listener)
    {
        $this->eventDispatcher->addListener('rollback', $listener);

        return $this;
    }

    /**
     * @param callable $listener
     *
     * @return self
     */
    public function onStop($listener)
    {
        $this->eventDispatcher->addListener('stop', $listener);

        return $this;
    }
}
