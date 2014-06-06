<?php

namespace Sokil\Mongo\Migrator;

class Revision
{
    private $_id;
    
    private $_name;
    
    private $_filename;
    
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }
    
    public function getId()
    {
        return $this->_id;
    }
    
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function setFilename($filename)
    {
        $this->_filename = $filename;
        return $this;
    }
    
    public function getFilename()
    {
        return $this->_filename;
    }
}