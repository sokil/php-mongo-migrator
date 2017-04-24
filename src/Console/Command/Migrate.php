<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sokil\Mongo\Migrator\Event\ApplyRevisionEvent;

class Migrate extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Migrate to specific revision of database')
            ->addOption(
                '--revision',
                '-r',
                InputOption::VALUE_OPTIONAL,
                'Revision of migration'
            )
            ->addOption(
                '--environment',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Environment name'
            )
            ->setHelp('Migrate to specific revision of database');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // version
        $revision = $input->getOption('revision');
        
        // environment
        $environment = $input->getOption('environment');
        if (!$environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
        }
        
        $output->writeln('Environment: <comment>' . $environment . '</comment>');
        
        // execute
        $this->getManager()
            ->onBeforeMigrateRevision(function (ApplyRevisionEvent $event) use ($output) {
                $revision = $event->getRevision();
                $output->writeln('Migration to revision <info>' . $revision->getId() . '</info> ' . $revision->getName() . ' ...');
            })
            ->onMigrateRevision(function (ApplyRevisionEvent $event) use ($output) {
                $output->writeln('done.');
            })
            ->migrate($revision, $environment);
    }
}
