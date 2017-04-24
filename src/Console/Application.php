<?php

namespace Sokil\Mongo\Migrator\Console;

class Application extends \Symfony\Component\Console\Application
{
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->add(new Command\Init);
        $this->add(new Command\Create);
        $this->add(new Command\Migrate);
        $this->add(new Command\Rollback);
        $this->add(new Command\Status);
    }
}
