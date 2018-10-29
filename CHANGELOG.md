## 0.7.0 (2018-10-29)
  * Stop support of PHP 5.3
  * Status command now return migration list sorted from early to latest migration
  * Option "--length" of command "status" may limit list by last revisions
  * Improved test environment

## 0.6.0 (2018-01-12)
  * Configure migrations through environment variables

## 0.5.0 (2017-02-01)
  * Support PHP7
  * Phar installer

## 0.4.0 (2017-01-04)
  * Support of Symfony Yaml, Console and Event Dispatcher components v.3

## 0.3.0 (2016-04-28)
  * Pass environment to migration class. It may be obtained by calling getEnvironment() method
  * Add init() method to migration class to run common code before up or down migrations

## 0.2.0 (2015-08-27)
  * Configuration of connect options