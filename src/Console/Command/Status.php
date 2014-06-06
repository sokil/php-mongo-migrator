<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Status extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Show status of migrations')
            ->addOption(
                '--environment', '-e',
                InputArgument::OPTIONAL, 
                'Environment name'
            )
            ->setHelp('Show list of migrations with status of applying');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // environment
        $environment = $input->getOption('environment');
        if(!$environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
        }
        
        // header
        $columnWidth = array(16, 8, 16);
        $output->writeln('');
        $output->writeln(' ' .
            str_pad('Revision', $columnWidth[0], ' ') . 
            str_pad('Status', $columnWidth[1], ' ') .
            str_pad('Name', $columnWidth[2], ' ')
        );
        
        $output->writeln(str_repeat('-', 35));
        
        // body
        $manager = $this->getManager();
        
        foreach($manager->getAvailableRevisions() as $revision) {
            
            if($manager->isRevisionApplied($revision->getId(), $environment)) {
                $status = '<info>up</info>' . str_repeat(' ', $columnWidth[1] - 2);
            } else {
                $status = '<error>down</error>' . str_repeat(' ', $columnWidth[1] - 4);
            }
            
            $output->writeln(' ' . 
                str_pad($revision->getId(), $columnWidth[0], ' ') . 
                $status . 
                str_pad($revision->getName(), $columnWidth[2], ' ')
            );
        }
        
        $output->writeln('');
    }
}