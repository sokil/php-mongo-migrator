<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create new migration')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Name of migration in CamelCase notation'
            )
            ->setHelp('Create new migration');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $className = $input->getArgument('name');
        if (!$className) {
            throw new \Exception('Name not specified');
        }
        
        if (!preg_match('/^([A-Z][a-z0-9]+)+$/', $className)) {
            throw new \Exception('Name must be in CamelCase notation');
        }
        
        $migrationFilename = date('YmdHis') . '_' . $className . '.php';
        $migrationFiledir = $this->getManager()->getMigrationsDir();
        $migrationFilepath =  $migrationFiledir . '/' . $migrationFilename;
        
        if (file_exists($migrationFilepath)) {
            throw new \Exception('Migration file with same name already exists');
        }
        
        if (!is_writeable(dirname($migrationFilepath))) {
            throw new \Exception('Permission denied for writting to ' . $migrationFilepath);
        }
        
        // create migrations file
        $migrationFileContent = str_replace(
            '{{MIGRATION_CLASSNAME}}',
            $className,
            file_get_contents(__DIR__ . '/../../MigrationTemplate.php.dist')
        );
        
        file_put_contents($migrationFilepath, $migrationFileContent);
        
        // show result
        $output->writeln('New migration created at ' . $migrationFilepath);
    }
}
