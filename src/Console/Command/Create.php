<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\Mongo\Migrator\Console\Command;

class Create extends Command
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
            ->addOption(
                '--configuration',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Configuration path'
            )
            ->setHelp('Create new migration');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws \Exception
     */
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
        $migrationFileDir = $this->getManager()->getMigrationsDir();
        $migrationFilePath =  $migrationFileDir . '/' . $migrationFilename;
        
        if (file_exists($migrationFilePath)) {
            throw new \Exception('Migration file with same name already exists');
        }
        
        if (!is_writeable(dirname($migrationFilePath))) {
            throw new \Exception('Permission denied for writting to ' . $migrationFilePath);
        }
        
        // create migrations file
        $migrationFileContent = str_replace(
            '{{MIGRATION_CLASSNAME}}',
            $className,
            file_get_contents(__DIR__ . '/../../../templates/MigrationTemplate.php.dist')
        );
        
        file_put_contents($migrationFilePath, $migrationFileContent);
        
        // show result
        $output->writeln('New migration created at ' . $migrationFilePath);
    }
}
