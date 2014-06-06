<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Rollback extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback to specific version of database')
            ->addOption(
                '--revision', '-r',
                InputArgument::OPTIONAL,
                'Revision of migration'
            )
            ->addOption(
                '--environment', '-e',
                InputArgument::OPTIONAL, 
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
        if(!$environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
        }
        
        // execute
        $this->getManager()
            ->onBeforeRollbackRevision(function() use($output) {
                $output->writeln('before');
            })
            ->onRollbackRevision(function() use($output) {
                $output->writeln('after');
            })
            ->rollback($revision, $environment);
    }
}