<?php

class Test3 extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        echo 'up3';
    }
    
    public function down()
    {
        echo 'down3';
    }
}