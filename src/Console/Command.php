<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\Config;
use Sokil\Mongo\Migrator\Console\Exception\ConfigurationNotFound;
use Sokil\Mongo\Migrator\Manager;
use Symfony\Component\Yaml\Yaml;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $path;

    /**
     *
     * @var \Sokil\Mongo\Migrator\Manager
     */
    private $manager;
    
    const CONFIG_FILENAME = 'mongo-migrator';

    /**
     * @param $path
     */
    protected function setConfigPath($path)
    {
        $this->path = $path;
    }

    /**
     *
     * @return \Sokil\Mongo\Migrator\Config
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = new Config($this->readConfig());
        }

        return $this->config;
    }
    
    private function readConfig()
    {
        if (!empty($this->path)) {
            return $this->readCustomConfig();
        }

        return $this->readDefaultConfig();
    }

    private function readCustomConfig()
    {
        if (!file_exists($this->path)) {
            throw new ConfigurationNotFound('Config not found');
        }

        $pathParts = pathinfo($this->path);

        if ($pathParts['extension'] === 'yaml') {
            return Yaml::parse(file_get_contents($this->path));
        }

        if ($pathParts['extension'] === 'php') {
            return require($this->path);
        }

        throw new ConfigurationNotFound('Config file must have yaml or php extension');
    }

    private function readDefaultConfig()
    {
        $filename = sprintf("%s/%s", $this->getProjectRoot(), self::CONFIG_FILENAME);

        $yamlFilename = $filename . '.yaml';
        if (file_exists($yamlFilename)) {
            return Yaml::parse(file_get_contents($yamlFilename));
        }

        $phpFilename = $filename . '.php';
        if (file_exists($phpFilename)) {
            return require($phpFilename);
        }

        throw new ConfigurationNotFound('Config not found');
    }

    /**
     * Check if  migration config present in project
     *
     * @return bool
     */
    public function isProjectInitialised()
    {
        try {
            $config = $this->getConfig();
            return (bool) $config;
        } catch (ConfigurationNotFound $e) {
            return false;
        }
    }

    /**
     * @return bool
     * @deprecated due to misspell in method name
     */
    public function isProjectInitialisd()
    {
        return $this->isProjectInitialised();
    }

    /**
     * Project root
     *
     * @return string
     */
    public function getProjectRoot()
    {
        return getcwd();
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->path;
    }
    
    /**
     *
     * @return \Sokil\Mongo\Migrator\Manager
     */
    public function getManager()
    {
        if (!$this->manager) {
            $this->manager = new Manager($this->getConfig(), $this->getConfigPath(), $this->getProjectRoot());
        }
        
        return $this->manager;
    }
}
