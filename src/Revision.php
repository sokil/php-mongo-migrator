<?php

namespace Sokil\Mongo\Migrator;

class Revision
{
    private $id;
    
    private $name;
    
    private $filename;
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }
    
    public function getFilename()
    {
        return $this->filename;
    }
}
