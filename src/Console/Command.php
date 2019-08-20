<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\Config;
use Sokil\Mongo\Migrator\Console\Exception\ConfigurationNotFound;
use Sokil\Mongo\Migrator\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     *
     * @var \Sokil\Mongo\Migrator\Manager
     */
    private $manager;
    
    const CONFIG_FILENAME = 'mongo-migrator';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws ConfigurationNotFound
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // config file path
        $configPath = $input->getOption('configuration');

        if (!empty($configPath)) {
            $this->config = new Config($this->readConfig($configPath));
        }
    }

    /**
     * @return \Sokil\Mongo\Migrator\Config
     * @throws ConfigurationNotFound
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = new Config($this->readConfig());
        }

        return $this->config;
    }

    /**
     * @param string $path
     * @return mixed
     * @throws ConfigurationNotFound
     */
    private function readConfig($path = '')
    {
        if (!empty($path) && file_exists($path)) {
            $pathParts = pathinfo($path);
            $filename = sprintf("%s/%s", $pathParts['dirname'], $pathParts['filename']);
        } else {
            $filename = sprintf("%s/%s", $this->getProjectRoot(), self::CONFIG_FILENAME);
        }

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
     *
     * @return \Sokil\Mongo\Migrator\Manager
     */
    public function getManager()
    {
        if (!$this->manager) {
            $this->manager = new Manager($this->getConfig(), $this->getProjectRoot());
        }
        
        return $this->manager;
    }
}
