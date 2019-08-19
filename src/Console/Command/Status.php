<?php

namespace Sokil\Mongo\Migrator\Console\Command;

use Sokil\Mongo\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Status extends \Sokil\Mongo\Migrator\Console\Command
{
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Show status of migrations')
            ->addOption(
                '--environment',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Environment name'
            )
            ->addOption(
                '--length',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Limit list by number of last revisions. If not set, show all revisions.'
            )
            ->addOption(
                '--configuration',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Configuration path'
            )
            ->setHelp('Show list of migrations with status of applying');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // config file path
        $configPath = $input->getOption('configuration');
        if ($configPath) {
            $this->setConfigPath($configPath);
        }

        // length of list
        $length = $input->getOption('length');
        if (empty($length)) {
            $length = null;
        } elseif (is_numeric($length)) {
            $length = (int)$length;
        } else {
            throw new \InvalidArgumentException('Length must be numeric, if specified');
        }

        // environment
        $environment = $input->getOption('environment');
        if (!$environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
        }
        
        $output->writeln('Environment: <comment>' . $environment . '</comment>');
        
        // header
        $columnWidth = array(16, 8, 16);
        $output->writeln('');
        $output->writeln(' ' .
            str_pad('Revision', $columnWidth[0], ' ') .
            str_pad('Status', $columnWidth[1], ' ') .
            str_pad('Name', $columnWidth[2], ' '));
        
        $output->writeln(str_repeat('-', 35));
        
        // body
        $manager = $this->getManager();
        
        foreach ($manager->getAvailableRevisions($length) as $revision) {
            if ($manager->isRevisionApplied($revision->getId(), $environment)) {
                $status = '<info>up</info>' . str_repeat(' ', $columnWidth[1] - 2);
            } else {
                $status = '<error>down</error>' . str_repeat(' ', $columnWidth[1] - 4);
            }
            
            $output->writeln(' ' .
                str_pad($revision->getId(), $columnWidth[0], ' ') .
                $status .
                str_pad($revision->getName(), $columnWidth[2], ' '));
        }
        
        $output->writeln('');
    }
}
