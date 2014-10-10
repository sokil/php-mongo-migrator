PHP Mongo Migrator
==================

Migrations for MongoDb.

[![Build Status](https://travis-ci.org/sokil/php-mongo-migrator.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo-migrator)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo-migrator/v/stable.png)](https://packagist.org/packages/sokil/php-mongo-migrator)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo-migrator/badge.png)](https://coveralls.io/r/sokil/php-mongo-migrator)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/sokil/php-mongo-migrator?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Installation
------------

Migrations allows you easily change schema and data versions. This functionality implemented in 
packet https://github.com/sokil/php-mongo-migrator and can be installed through composer:

```php
{
    "require": {
        "sokil/php-mongo-migrator": "dev-master"
    }
}
```

After installation you will be able to run commands in console by running ./vendor/bin/mongo-migrator command.

Initialising migrations
-----------------------

Every command run in project root where composer.json and vendor dir placed. First we need to create 
new migration project. To do that go to project root and run:
```
vendor/bin/mongo-migrator init
```

This creates config file mongo-migrator.yaml and directory "./migrations", where migrations placed.

Configuration
-------------

Conviguration file placed in file "./mongo-migrator.yaml".

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

* environments.*.default_database - databse, used if no database specified id migration script

* environments.*.log_database - database, used to store migration log

* environments.*.log_collection - collection of database environments.*.log_database used to store migration log

Creating new revision
---------------------
Now you can create your initial migration script. Creating new revison:
```
vendor/bin/mongo-migrator create revision_name
```

Name of revision must be in camel case format. For example run ```vendor/bin/mongo-migrator create RevisionName```. 
This creates migration script like 20140607132630_RevisionName.php, where "20140607132630"
is revision id and "RevisionName" is revision name. Class source is:

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
    public function up()
    {
        $collection = $this
            ->getDatabase('some_database')
            ->getCollection('come_collection');

        // create new field in all documents of collection
        $collection->updateAll(
            $collection->operator()->set('newField', 'defaultValue')
        );
    }
    
    public function down()
    {
        $collection = $this
            ->getDatabase('some_database')
            ->getCollection('come_collection');

        // create new field in all documents of collection
        $collection->updateAll(
            $collection->operator()->unsetField('newField')
        );
    }
}
```

