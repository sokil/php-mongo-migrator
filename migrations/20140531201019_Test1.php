<?php

class Test1 extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        echo 'up1';
    }
    
    public function down()
    {
        echo 'down1';
    }
}