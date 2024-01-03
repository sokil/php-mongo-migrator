# Stand With Ukraine 🇺🇦

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

PHPMongo Migrator
==================

Migrations for MongoDB based on [PHPMongo ODM](https://github.com/sokil/php-mongo)

[![Daily Downloads](https://poser.pugx.org/sokil/php-mongo-migrator/d/daily)](https://packagist.org/packages/sokil/php-mongo-migrator)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo-migrator/v/stable.png)](https://packagist.org/packages/sokil/php-mongo-migrator)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo-migrator/badge.png)](https://coveralls.io/r/sokil/php-mongo-migrator)
[![Gitter](https://badges.gitter.im/Join_Chat.svg)](https://gitter.im/sokil/php-mongo-migrator?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Schema not required in MongoDb, so we dont need to create databases, collections or altering them. However there are some cases when migrations required in schemaless databases:
* Creating collections with special parameters, like capped collection;
* Renaming or deleting collections;
* Creating, renaming or deleting fields;
* Creating, changing or deleting indexes;

Requirements
------------

* PHP 5
  * PHP 5.3 not supported starting from 2018-10-19
  * PHP 5.4 - PHP 5.6
  * [PHP Mongo Extension](https://pecl.php.net/package/mongo) 0.9 or above (Some features require >= 1.5)
* PHP 7
  * [PHP MongoDB Extension](https://pecl.php.net/package/mongodb) 1.0 or above
  * [Compatibility layer](https://github.com/alcaeus/mongo-php-adapter). Please, note some [restrictions](#compatibility-with-php-7)
* HHVM
  * HHVM Driver [not supported](https://derickrethans.nl/mongodb-hhvm.html).
<br/>
<br/>

Installation
------------

#### Install locally through composer

```
composer require sokil/php-mongo-migrator
```

After installation you will be able to run commands in console by running ./vendor/bin/mongo-migrator command.

#### Install using phive

```
phive install sokil/php-mongo-migrator
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

Usage
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

Initialisation of migrations
----------------------------

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

You may explicitly define path to conficuration file, and also to migration dir:

```
vendor/bin/mongo-migrator init --configuration=confins/monfo-migrations.yaml --migrationDir=../migrations/mongo
``` 

If migration dir defined relatively, it points to dir where configuration stored. In example above migrations 
dir will be `confins/../migrations/mongo`. 

Configuration
-------------

#### Configuration format

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

#### Environment variables in configuration

Any value may be initialised from environment variable:

```yaml

default_environment: common

path:
    migrations:  "%env(MONGO_MIGRATIONS_PATH)%"

environments:
    common:
        dsn: "%env(MONGO_DSN)%"
        default_database: "%env(MONGO_DEFAULT_DB)%"
        log_database: "%env(MONGO_LOG_DB)%"
        log_collection: "%env(MONGO_LOG_COLLECTION)%"
```

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
vendor/bin/mongo-migrator status [-e|--environment environment=ENVIRONMENT] [-c|--configuration=CONFIGURATION] [-l|--length=LENGTH]
```

If revision status is "up", revision is applied, otherwise status will be "down".
```
 Revision        Status  Name            
-----------------------------------       
 20140607165612  down    Test2           
 20140607141237  up      Test1           
 20140607132630  up      RevisionName
```

Option `configuration` allows specify path to project configuration, if it differ from default path. 
Option `length` allows to limit elements in list.

Migrating and rollback
----------------------

You can migrate and rollback to any of available revisions. Commands to migrate:

```
vendor/bin/mongo-migrator migrate  [-r|--revision revision] [-e|--environment environment] [-c|--configuration configuration]
```
If revision not specified, migration goes to latest revision.

Option `configuration` allows specify path to project configuration, if it differ from default path.
 
Command to rollback:
```
vendor/bin/mongo-migrator rollback [-r|--revision revision] [-e|--environment environment] [-c|--configuration configuration]
```
If revision not specified, rollback goes to previous revision.

Option `configuration` allows specify path to project configuration, if it differ from default path.

Writing migration scripts
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

1) Install box using manual at https://github.com/box-project/box2. It must be accessible as `box`

2) Check that `composer` installed and accessible in PATH

3) You may build phar in three modes: unsigned version, signed by OPENSSH (for self test on run) and signed by GPG (for installation through phive)
 
3.1) To build unsigned version just run make
```
make
````

3.2) To build phar signed with OPENSSH, you need to have own private key. 
Copy it to `./keys/private.pem` or generate new one:
```
# Generate new one:
openssl genrsa -des3 -out private.pem 4096
# If you want to remove passphrase
openssl rsa -in private.pem -out private.pem
# generate public
openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```
Then build phar:
```
make openssh-signed
````

3.3) To build phar sighen with GPG for phive, you need to place private key to `./keys/private.ask`:
```
gpg --gen-key
gpg --export-secret-keys your@mail.com > keys/private.asc
```
Then build GPG-signed phar:
```
make gpg-signed
````

You may verify phar by public key:
```
$ gpg --verify mongo-migrator.phar.asc mongo-migrator.phar
gpg: Signature made чт, 22-лис-2018 23:27:46 +0200 EET
gpg:                using RSA key F530929F7ED528F0
gpg:                issuer "dmytro.sokil@gmail.com"
gpg: Good signature from "Dmytro Sokil <dmytro.sokil@gmail.com>" [ultimate]

```

You may build phars both for legacy and new driver by defining `MONGO_DRIVER` env variable:

```
make gpg-signed MONGO_DRIVER=new
make gpg-signed MONGO_DRIVER=legacy
```

If `MONGO_DRIVER` env variable not passed, then `make` will try to detect your driver automatically.

Development
-----------

To start development environment in docker run:
```
./run-docker-cli.sh
```

To use `xdebug`, configure your IDE to use port 9001.

There is sandbox to test commands:

```
cd /phpmongo/tests/
export PHPMONGO_DSN="mongodb://mongodb32"; ../bin/mongo-migrator -vvv status -l 4 -e docker
``` 

Unit tests
----------

Local tests:

```
composer.phar test
```

Docker tests:

```
./run-docker-tests.sh
```


