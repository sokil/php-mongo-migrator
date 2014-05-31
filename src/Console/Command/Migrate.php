<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Migrate to specific revision of database')
            ->addOption(
                '--revision', '-r',
                InputArgument::OPTIONAL,
                'Revision of migration'
            )
            ->addOption(
                '--environment', '-e',
                InputArgument::OPTIONAL, 'Environment name', 
                $this->getConfig()->getDefaultEnvironment()
            )
            ->setHelp('Migrate to specific revision of database');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // version
        $revision = $input->getOption('revision');
        
        // environment
        $environment = $input->getOption('environment');
        
        // execute
        $this->getManager()->migrate($revision, $environment);
    }
}