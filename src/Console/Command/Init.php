<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

class Init extends \Sokil\Mongo\Migrator\Console\Command
{
   private $allowedConfigFormats = array('yaml', 'php');
   
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize migrations project')
            ->setHelp('Create migrations project')
            ->addOption(
                '--configFormat', '-f',
                InputOption::VALUE_OPTIONAL,
                'Format of config (use one of "' . implode('","', $this->allowedConfigFormats) . '")',
                'yaml'
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($this->isProjectInitialisd()) {
            throw new \Exception('Migration project already initialised');
        }
        
        // check permissions
        $configPath = $this->getProjectRoot();
        if(!is_writable($this->getProjectRoot())) {
            throw new \Exception('Directory ' . $configPath . ' must be writeabe');
        }
        
        $configFormat = $input->getOption('configFormat');
        if(!in_array($configFormat, $this->allowedConfigFormats)) {
            throw new \Exception('Config format "' . $configFormat . '" not allowed');
        }
        
        // copy config to target path
        $configPatternPath = __DIR__ . '/../../../templates/' . self::CONFIG_FILENAME . '.' . $configFormat;
        $targetConfigPath = $configPath . '/' . self::CONFIG_FILENAME . '.' . $configFormat;
        if(!copy($configPatternPath, $targetConfigPath)) {
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