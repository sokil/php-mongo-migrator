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

    public function __construct(Config $config, $rootDir)
    {
        $this->config = $config;
        $this->rootDir = $rootDir;
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

        return $this->rootDir . '/' . rtrim($migrationsDir, '/');
    }

    /**
     * @return Revision[]
     */
    public function getAvailableRevisions()
    {
        $list = array();
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

            krsort($list);
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
    protected function executeMigration($targetRevision, $environment, $direction, $specifiedRevision = null)
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
                if ($revision->getId() <= $latestRevisionId && empty($specifiedRevision)) {
                    continue;
                }
                if (
                (
                    !empty($specifiedRevision) &&
                    (
                        $revision->getId() != $specifiedRevision ||
                        in_array($specifiedRevision, $this->appliedRevisions[$environment])
                    )
                )
                ) {
                    continue;
                }

                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);

                $this->eventDispatcher->dispatch('before_migrate_revision', $event);

                $revisionPath = $this->getMigrationsDir() . '/' . $revision->getFilename();
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

                if (
                    $targetRevision &&
                    in_array($targetRevision, array($revision->getId(), $revision->getName())) &&
                    empty($specifiedRevision)
                ) {
                    break;
                }

                if (!empty($specifiedRevision) && ($revision->getId() != $specifiedRevision)) {
                    continue;
                }
                if (
                    !empty($specifiedRevision) &&
                    !in_array($specifiedRevision, $this->appliedRevisions[$environment])
                ) {
                    break;
                }


                $event = new ApplyRevisionEvent();
                $event->setRevision($revision);
                if ($this->isLockedRevision($environment, $revision, $event)) {
                    break;
                }

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
     * @param string $environment
     * @param Revision $revision
     * @param ApplyRevisionEvent $event
     */
    private function isLockedRevision($environment, $revision, $event)
    {
        $maxTimeLock = $this->config->getMaxTimeLock();
        if (empty($maxTimeLock)) {
            return false;
        }
        $revisionDoc = $this->getLogCollection($environment)
            ->find()->where('revision', $revision->getId())->one();
        $revisionedTime = $revisionDoc->date->sec;
        if (strtotime($maxTimeLock) > $revisionedTime) {
            $this->eventDispatcher->dispatch('rollback_error', $event);
            return true;
        }
        return false;
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
    public function migrate($revision, $environment, $specifiedRev = null)
    {
        $this->executeMigration($revision, $environment, 1, $specifiedRev);

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
    public function rollback($revision, $environment, $specifiedRev = null)
    {
        $this->executeMigration($revision, $environment, -1, $specifiedRev);

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
    public function onRollbackError($listener)
    {
        $this->eventDispatcher->addListener('rollback_error', $listener);

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
