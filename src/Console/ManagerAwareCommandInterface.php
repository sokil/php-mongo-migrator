<?php

namespace Sokil\Mongo\Migrator\Console;

use Sokil\Mongo\Migrator\Manager;

interface ManagerAwareCommandInterface
{
    /**
     * @return Manager
     */
    public function getManager();
}