<?php

class Test5 extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        echo 'up5';
    }
    
    public function down()
    {
        echo 'down5';
    }
}