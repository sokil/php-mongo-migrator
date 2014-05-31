<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize migrations project')
            ->setHelp('Create migrations project');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $configPath = $this->getConfigPath();
        
        // check permissions
        if(!is_writable(dirname($configPath))) {
            throw new \Exception('Directory ' . $configPath . ' must be writeabe');
        }
        
        // copy config to target path
        $configPatternPath = __DIR__ . '/../../../' . self::CONFIG_FILENAME;
        if(!copy($configPatternPath, $configPath)) {
            throw new \Exception('Can\'t write config to target directory <info>' . $configPath . '</info>');
        }
        
        $output->writeln('Project config "mongo-migrator.yaml" created at <info>' . $configPath . '</info>');
        
        // create migrations dir
        $migrationsDirectory = $this->getConfig()->getMigrationsDir();
        
        if(!file_exists($migrationsDirectory)) {            
            if(!mkdir($migrationsDirectory, 0755, true)) {
                throw new \Exception('Can\'t create migrations directory ' . $migrationsDirectory);
            }
        }
        
        $output->writeln('Directory for migrations created at <info>' . $migrationsDirectory . '</info>');
    }
}