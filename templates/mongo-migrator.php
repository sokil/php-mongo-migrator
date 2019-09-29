<?php

return array(
    'default_environment' => 'development',
    'path' => array(
        // migrations path is relative to current configuration dir
        'migrations' => 'migrations'
    ),
    'environments' => array(
        'development' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
        ),
        'staging' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
        ),
        'production' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
        ),
    ),
);
