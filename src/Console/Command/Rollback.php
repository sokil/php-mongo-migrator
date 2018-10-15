<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sokil\Mongo\Migrator\Event\ApplyRevisionEvent;

class Rollback extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback to specific version of database')
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
            ->addOption(
                '--specifiedRevision',
                '-s',
                InputOption::VALUE_OPTIONAL,
                'Environment name'
            )
            ->setHelp('Rollback to specific revision of database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // version
        $revision = $input->getOption('revision');

        // environment
        $environment = $input->getOption('environment');

        $specifiedRev = $input->getOption('specifiedRevision');

        if (!$environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
        }

        $output->writeln('Environment: <comment>' . $environment . '</comment>');

        // execute
        $this->getManager()
            ->onBeforeRollbackRevision(function (ApplyRevisionEvent $event) use ($output) {
                $revision = $event->getRevision();
                $output->writeln('Rollback to revision <info>' . $revision->getId() . '</info> ' . $revision->getName() . ' ...');
            })
            ->onRollbackRevision(function (ApplyRevisionEvent $event) use ($output) {
                $output->writeln('done.');
            })
            ->rollback($revision, $environment, $specifiedRev);
    }
}
