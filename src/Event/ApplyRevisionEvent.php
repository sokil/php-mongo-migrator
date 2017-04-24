<?php

namespace Sokil\Mongo\Migrator\Event;

use Sokil\Mongo\Migrator\Revision;

class ApplyRevisionEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     *
     * @var \Sokil\Mongo\Migrator\Revision
     */
    private $revision;
    
    public function setRevision(Revision $revision)
    {
        $this->revision = $revision;
        return $this;
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Migrator\Revision
     */
    public function getRevision()
    {
        return $this->revision;
    }
}