PHPMongo Migrator
==================

Migrations for MongoDB based on [PHPMongo ODM](https://github.com/sokil/php-mongo)

[![Build Status](https://travis-ci.org/sokil/php-mongo-migrator.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo-migrator)<!--[![Total Downloads](http://img.shields.io/packagist/dt/sokil/php-mongo-migrator.svg)](https://packagist.org/packages/sokil/php-mongo-migrator)-->
[![Daily Downloads](https://poser.pugx.org/sokil/php-mongo-migrator/d/daily)](https://packagist.org/packages/sokil/php-mongo-migrator)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo-migrator/v/stable.png)](https://packagist.org/packages/sokil/php-mongo-migrator)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo-migrator/badge.png)](https://coveralls.io/r/sokil/php-mongo-migrator)
[![Gitter](https://badges.gitter.im/Join_Chat.svg)](https://gitter.im/sokil/php-mongo-migrator?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Schema not required in MongoDb, so we dont need to create databases, collections or altering them. However there are some cases when migrations required in schemaless databases:
* Creating collections with special parameters, like capped collection;
* Renaming or deleting collections;
* Creating, renaming or deleting fields;
* Creating, changing or deleting indexes

Requirements
------------

* PHP 5
  * PHP 5.3 - PHP 5.6
  * [PHP Mongo Extension](https://pecl.php.net/package/mongo) 0.9 or above (Some features require >= 1.5)
* PHP 7 and HHVM
  * [PHP MongoDB Extension](https://pecl.php.net/package/mongodb) 1.0 or above
  * [Compatibility layer](https://github.com/alcaeus/mongo-php-adapter). Please, note some [restriontions](#compatibility-with-php-7)
  * HHVM Driver [not supported](https://derickrethans.nl/mongodb-hhvm.html).
* Tested over MongoDB v.2.4.12, v.2.6.9, v.3.0.2, v.3.2.10, v.3.3.15, v.3.4.0 (See [Unit tests](#unit-tests))
* [Symfony Event Dispatcher](http://symfony.com/doc/current/components/event_dispatcher/introduction.html)
* [GeoJson version ~1.0](https://github.com/jmikola/geojson)
* [PSR-3 logger interface](https://github.com/php-fig/log)
<br/>
<br/>

Installation
------------

#### Install locally through composer


```
composer require sokil/php-mongo-migrator
```

After installation you will be able to run commands in console by running ./vendor/bin/mongo-migrator command.

#### Install phar

Run in shell:
```
wget http://phpmongokit.github.io/dists/mongo-migrator.phar && chmod +x mongo-migrator.phar && sudo mv mongo-migrator.phar /usr/local/bin
```

#### Compatibility with PHP 7

> PHPMongo currently based on old [ext-mongo](https://pecl.php.net/package/mongo) entension.
> To use this ODM with PHP 7, you need to add [compatibility layer](https://github.com/alcaeus/mongo-php-adapter), which implement API of old extension over new [ext-mongodb](https://pecl.php.net/package/mongodb).
> To start using PHPMongo with PHP7, add requirement [alcaeus/mongo-php-adapter](https://github.com/alcaeus/mongo-php-adapter) to composer.
> Restrictions for using ODM with compatibility layer you can read in [known issues](https://github.com/alcaeus/mongo-php-adapter#known-issues) of original adapter.

You need to require adapter:
```
composer require alcaeus/mongo-php-adapter
```

Useage
------

```
$ ./mongo-migrator
Console Tool

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  create    Create new migration
  help      Displays help for a command
  init      Initialize migrations project
  list      Lists commands
  migrate   Migrate to specific revision of database
  rollback  Rollback to specific version of database
  status    Show status of migrations
```

Initialising migrations
-----------------------

Every command run in project root where composer.json and vendor dir placed. First we need to create 
new migration project. To do that go to project root and run:
```
vendor/bin/mongo-migrator init
```

This creates config file mongo-migrator.yaml and directory "./migrations", where migrations placed.
Also you can use php config instead of yaml. Just initialise your project with php config format:
```
vendor/bin/mongo-migrator init --configFormat=php
```

Configuration
-------------

YAML configuration file placed in file "./mongo-migrator.yaml". PHP has same structure.

```yaml
default_environment: development

path:
    migrations: migrations
    
environments:
    development:
        dsn: mongodb://localhost
        
        default_database: test
        
        log_database: test
        log_collection: migrations
    
    staging:
        dsn: mongodb://localhost
        
        default_database: test
        
        log_database: test
        log_collection: migrations
    
    production:
        dsn: mongodb://localhost
        
        default_database: test
        
        log_database: test
        log_collection: migrations
```

Environment is set of configuration parameters, defined for concrete place, like 
development machine, test or production server. 

* default_environment - some commands required to know environment, where they executed.
This parameters defines which environment to use if environment not specified.

* path.migrations - path to migrations directory, where migration scripts placed.

* environments - section of environment configs. 

Every environment has this parameters:

* environments.*.dsn - DSN which used to connect to mongo server

* environments.*.connectOptions - options of MongoClient, described in [\MongoClient PHP manual](http://php.net/manual/ru/mongoclient.construct.php)

* environments.*.default_database - databse, used if no database specified id migration script

* environments.*.log_database - database, used to store migration log

* environments.*.log_collection - collection of database environments.*.log_database used to store migration log

Creating new revision
---------------------
Now you can create your migration script. Creating new revison:
```
vendor/bin/mongo-migrator create revision_name
```

Name of revision must be in camel case format. For example run ```vendor/bin/mongo-migrator create RevisionName```. 
This creates migration script like 20151127093706_RevisionName.php, where "20151127093706"
is revision id and "RevisionName" is revision name. 

```
pc:~/php-mongo-migrator$ ./bin/mongo-migrator create RevisionName
New migration created at ~/php-mongo-migrator/migrations/20151127093706_RevisionName.php
```

Class source is:

```php
<?php

class RevisionName extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        
    }
    
    public function down()
    {
        
    }
}
``` 

Method up() filled with commands executed on migrating process, and down() - on rollback.

Now you can write code for migration and rollback.

Status of migration
-------------------
If you want to see list of existed revisions with status of migration, run:
```
vendor/bin/mongo-migrator status [-e environment]
```

If revision status is "up", revision is applied, otherwise status will be "down".
```
 Revision        Status  Name            
-----------------------------------       
 20140607165612  down    Test2           
 20140607141237  up      Test1           
 20140607132630  up      RevisionName
```

Migrating and rollback
----------------------

You can migrate and rollback to any of available revisions. Commands to migrate:

```
vendor/bin/mongo-migrator migrate  [-r revision] [-e environment]
```
If revision not specified, migration goes to latest revision.
 
Command to rollback:
```
vendor/bin/mongo-migrator rollback [-r revision] [-e environment]
```
If revision not specified, rollback goes to previous revision.

Writting migration scripts
--------------------------

Databases and collections accessable from migration script through methods 
AbstractMigration::getDatabase and AbstractMigration::getCollection. Method
AbstractMigration::getCollection get's collection of default database, defined in  
"environments.*.default_database" parameter of config.

Documentation on database and collection classes may be 
found in https://github.com/sokil/php-mongo.

```php
<?php

class RevisionName extends \Sokil\Mongo\Migrator\AbstractMigration
{
    protected function init() 
    {
        // some common code
    }
    
    public function up()
    {
        $collection = $this
            ->getDatabase('some_database')
            ->getCollection('come_collection');

        // create new field in all documents of collection
        $collection->updateAll(function($operator) {
            $operator->set('newField', 'defaultValue')
        });
    }
    
    public function down()
    {
        $collection = $this
            ->getDatabase('some_database')
            ->getCollection('come_collection');

        // create new field in all documents of collection
        $collection->updateAll(function($operator) {
            $operator->unsetField('newField')
        });
    }
}
```

Building Phar
-------------

1) Update composer
```
composer.phar update --ignore-platform-reqs --no-dev -o
```

2) Install box using manual at https://github.com/box-project/box2
```
composer global require kherge/box --prefer-source
```

2) Build phar
```
box build -v
````


