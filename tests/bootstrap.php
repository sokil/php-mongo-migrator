<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Sokil\\Mongo\\Migrator\\', __DIR__);

// check mongo connection presence
$client = new \Sokil\Mongo\Client();
try {
    $client->getMongoClient()->connect();
} catch (MongoConnectionException $e) {
    die('Error connecting to mongo server');
}

