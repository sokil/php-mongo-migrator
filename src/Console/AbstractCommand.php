<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\ManagerBuilder;
use Symfony\Component\Console\Command\Command;
use Sokil\Mongo\Migrator\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * @var Manager
     */
    private $manager;

    protected function configure()
    {
        parent::configure();

        if ($this instanceof ManagerAwareCommandInterface) {
            $this->addOption(
                '--configuration',
                '-c',
                InputOption::VALUE_REQUIRED,
                'The configuration file'
            );
        }

        if ($this instanceof EnvironmentRelatedCommandInterface) {
            $this->addOption(
                '--environment',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Environment name'
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

        if ($this instanceof ManagerAwareCommandInterface) {
            // get path to configuration
            $configurationPath = $input->getOption('configuration');

            $this->initialiseManager($configurationPath);
        }
    }

    /**
     * @param null|string $configurationPath
     */
    protected function initialiseManager($configurationPath = null)
    {
        $managerBuilder = new ManagerBuilder();

        // create manager
        $this->manager = $managerBuilder->build(
            $this->getProjectRoot(),
            $configurationPath
        );
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
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
