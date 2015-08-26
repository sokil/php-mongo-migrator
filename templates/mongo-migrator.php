<?php

return array(
    'default_environment' => 'development',
    'path' => array(
        'migrations' => 'migrations'
    ),
    'environments' => array(
        'development' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
            'options' => [
                'replicaSet' => '',
                'connect' => false,
                'connectTimeoutMS' => 1000,
                'readPreference' => \MongoClient::RP_PRIMARY
            ]
        ),
        'staging' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
            'options' => [
                'replicaSet' => '',
                'connect' => false,
                'connectTimeoutMS' => 1000,
                'readPreference' => \MongoClient::RP_PRIMARY
            ]
        ),
        'production' => array(
            'dsn' => 'mongodb',
            'default_database' => 'test',
            'log_database' => 'test',
            'log_collection' => 'migrations',
            'options' => [
                'replicaSet' => '',
                'connect' => false,
                'connectTimeoutMS' => 1000,
                'readPreference' => \MongoClient::RP_PRIMARY
            ]
        ),
    ),
);
