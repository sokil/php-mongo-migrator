<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Sokil\Mongo\Migrator\ManagerBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Sokil\Mongo\Migrator\Console\AbstractCommand;

class Init extends AbstractCommand
{
    /**
     * If directory is relative, it relates to dir with configuration file
     */
    const DEFAULT_MIGRATIONS_DIR = 'migrations';

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
            )
            ->addOption(
                '--migrationDir',
                '-d',
                InputOption::VALUE_REQUIRED,
                'Directory with migration files. May be absolute or relative to configuration dir.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get configuration path
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
            $output->writeln('<error>Path to configuration file need to be specified, directory found</error>');

            return 1;
        }

        if (file_exists($configurationPath)) {
            $output->writeln('<error>Migration project already initialised</error>');

            return 1;
        }

        if (!is_writable(dirname($configurationPath))) {
            $output->writeln(sprintf(
                '<error>Can not write configuration to %s, directory is not writable</error>',
                dirname($configurationPath)
            ));

            return 1;
        }

        // check config format
        if (empty($configFormat)) {
            $output->writeln(sprintf(
                '<error>Config file must be with one of extensions: %s</error>',
                implode(', ', ManagerBuilder::ALLOWED_CONFIG_FORMATS)
            ));

            return 1;
        } elseif (!in_array($configFormat, ManagerBuilder::ALLOWED_CONFIG_FORMATS)) {
            $output->writeln('<error>Config format "' . $configFormat . '" not allowed</error>');

            return 1;
        }

        // get migrations dir
        $migrationDir = $input->getOption('migrationDir');
        if (empty($migrationDir)) {
            $migrationDir = self::DEFAULT_MIGRATIONS_DIR;
        }

        // render configuration from template
        $configTemplatePath = __DIR__ . '/../../../templates/configurationTemplate' . '.' . $configFormat . '.dist';

        $configBody = str_replace(
            [
                '{{MIGRATIONS_DIR}}',
            ],
            [
                $migrationDir,
            ],
            file_get_contents($configTemplatePath)
        );

        // write configuration to file
        if (!file_put_contents($configurationPath, $configBody)) {
            $output->writeln('<error>Can\'t write config to </error><info>' . $configurationPath . '</info>');

            return 1;
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
