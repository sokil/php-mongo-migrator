<?php

class Test2 extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        echo 'up2';
    }
    
    public function down()
    {
        echo 'down2';
    }
}