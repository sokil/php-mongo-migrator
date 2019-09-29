<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Sokil\Mongo\Migrator\ManagerBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Sokil\Mongo\Migrator\Console\AbstractCommand;

class Init extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('init')
            ->setDescription('Initialize migrations project')
            ->setHelp('Create migrations project')
            ->addOption(
                '--configFormat',
                '-f',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Format of config (use one of "%s"). Must be skipped if --configuration parameter specified.',
                    implode('","', ManagerBuilder::ALLOWED_CONFIG_FORMATS)
                ),
                ManagerBuilder::FORMAT_YAML
            )
            ->addOption(
                '--configuration',
                '-c',
                InputOption::VALUE_REQUIRED,
                'The configuration file to create. Must be skipped if --configFormat parameter specified'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurationPath = $input->getOption('configuration');

        if (empty($configurationPath)) {
            // get config format
            $configFormat = $input->getOption('configFormat');

            // build path to default config
            $configurationPath = sprintf(
                '%s/%s.%s',
                getcwd(),
                ManagerBuilder::DEFAULT_CONFIG_FILENAME,
                $configFormat
            );
        } else {
            $configFormat = pathinfo($configurationPath, PATHINFO_EXTENSION);
        }

        // check if configuration path is valid and may be written
        if (substr($configurationPath, -1) === '/') {
            throw new \Exception('File need to be specified, directory found');
        }

        if (file_exists($configurationPath)) {
            throw new \Exception('Migration project already initialised');
        }

        if (!is_writable(dirname($configurationPath))) {
            throw new \Exception(sprintf(
                'Can not write configuration to %s, directory is not writable',
                dirname($configurationPath)
            ));
        }

        if (empty($configFormat)) {
            throw new \Exception(sprintf(
                'Config file must be with one of extensions: %s',
                implode(', ', ManagerBuilder::ALLOWED_CONFIG_FORMATS)
            ));
        } elseif (!in_array($configFormat, ManagerBuilder::ALLOWED_CONFIG_FORMATS)) {
            throw new \Exception('Config format "' . $configFormat . '" not allowed');
        }

        // copy config to target path
        $configPatternPath = __DIR__ . '/../../../templates/' . ManagerBuilder::DEFAULT_CONFIG_FILENAME . '.' . $configFormat;
        if (!copy($configPatternPath, $configurationPath)) {
            throw new \Exception('Can\'t write config to <info>' . $configurationPath . '</info>');
        }
        
        $output->writeln(
            sprintf(
                'Project configuration created at <info>%s</info>',
                $configurationPath
            )
        );

        // init manager
        $this->initialiseManager($configurationPath);

        // create directory for migrations
        $this->getManager()->createMigrationsDir();
        
        $output->writeln(
            sprintf(
                'Directory for migrations created at <info>%s</info>',
                $this->getManager()->getMigrationsDir()
            )
        );
    }
}
