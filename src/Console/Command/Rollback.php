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
            ->addOption('--version', '-v', InputOption::VALUE_REQUIRED, 'Version of migration')
            ->setHelp('Rollback to specific version of database');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption('version');
        
        $output->writeln('Rollback to version' . $version);
    }
}