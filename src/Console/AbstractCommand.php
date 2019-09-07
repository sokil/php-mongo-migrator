<?php

namespace Sokil\Mongo\Migrator\Console;

use Symfony\Component\Console\Command\Command;
use Sokil\Mongo\Migrator\Config;
use Sokil\Mongo\Migrator\Console\Exception\ConfigurationNotFound;
use Sokil\Mongo\Migrator\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractCommand extends Command
{
    const FORMAT_YAML = 'yaml';
    const FORMAT_PHP = 'php';

    /**
     * @var array
     */
    const ALLOWED_CONFIG_FORMATS = array(
        self::FORMAT_YAML,
        self::FORMAT_PHP,
    );

    /**
     * @var Config
     */
    private $config;

    /**
     *
     * @var \Sokil\Mongo\Migrator\Manager
     */
    private $manager;
    
    const DEFAULT_CONFIG_FILENAME = 'mongo-migrator';

    protected function configure()
    {
        parent::configure();

        if ($this instanceof ConfigurationAwareInterface) {
            $this->addOption(
                '--configuration',
                '-c',
                InputOption::VALUE_REQUIRED,
                'The configuration file'
            );
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($this instanceof ConfigurationAwareInterface) {
            // get path to configuration
            $configurationPath = $input->getOption('configuration');

            if (empty($configurationPath)) {
                $configurationPath = $this->locateDefaultConfigurationPath();
            }

            // load configuration
            $this->config = $this->loadConfiguration($configurationPath);
        }
    }

    /**
     * @return string
     */
    private function locateDefaultConfigurationPath()
    {
        $filename = $this->getProjectRoot() . '/' . self::DEFAULT_CONFIG_FILENAME;

        foreach (self::ALLOWED_CONFIG_FORMATS as $allowedConfigFormat) {
            $configurationPath = sprintf('%s.%s', $filename, $allowedConfigFormat);
            if (file_exists($configurationPath)) {
                return $configurationPath;
            }
        }

        throw new ConfigurationNotFound('Configuration not found');
    }

    /**
     * @param string $configurationPath
     *
     * @return Config
     *
     * @throws ConfigurationNotFound
     */
    private function loadConfiguration($configurationPath)
    {
        // check if config readable
        if (!is_readable($configurationPath)) {
            throw new \InvalidArgumentException('Passed configuration path is not readable');
        }

        $configurationFormat = pathinfo($configurationPath, PATHINFO_EXTENSION);

        switch ($configurationFormat) {
            case self::FORMAT_YAML:
                $configuration = Yaml::parse(file_get_contents($configurationPath));
                break;
            case self::FORMAT_PHP:
                $configuration = require($configurationPath);
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Passed configuration path must be in one of allowed formats %s',
                        implode(', ', self::ALLOWED_CONFIG_FORMATS)
                    )
                );
        }

        return new Config($configuration);
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
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
