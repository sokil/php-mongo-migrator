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
            ->setDescription('Migrate to specific version of database')
            ->addOption('--version', '-v', InputOption::VALUE_OPTIONAL, 'Version of migration')
            ->addOption('--environment', '-e', InputOption::VALUE_OPTIONAL, 'Environment name')
            ->setHelp('Migrate to specific version of database');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption('version');
        
        $output->writeln('Migrate to version' . $version);
    }
}