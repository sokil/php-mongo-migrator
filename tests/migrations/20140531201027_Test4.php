<?php

class Test4 extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        echo 'up4';
    }
    
    public function down()
    {
        echo 'down4';
    }
}