Wikidata 'Build' Git Repository
=========

Wikidata is using a build with Wikibase and it's dependencies packaged into one git repo.

This git repo contains everything you need to deploy Wikidata (Wikibase and all of its dependencies).

## Installation

  - Pull the Git Repository

Add the following line to your LocalSettings.php
```php
require_once("$IP/extensions/Wikidata/Wikidata.php");
```
This entry point in turn loads all other entry points

## Configuration

Wikibase itself needs to be configured, with appropriate settings. See the below links:

  - https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
  - https://www.mediawiki.org/wiki/Extension:Wikibase_Client

Using this repo provides extra options to allow you to choose to deploy the Repo and/or Client.

```php
// Load the Repo Extension (default false)
$wmgUseWikibaseRepo = true;
// Load the Client Extension (default false)
$wmgUseWikibaseClient = true;
```

## Maintenance scripts

The Maintenance scripts help within this repo will not work if you do not have the environment variable **MW_INSTALL_PATH** defined.

If you do not and can not define this variable please use the **runScript.php** maintenance script within mediawiki core (see comments in that file for instructions)

## Make a build

Making a Wikidata build requires composer to be installed on the system. (http://getcomposer.org)

  - run build.sh that is provided

The script clones Wikibase, init submodules in Wikibase (easyRdf), and runs composer install to pull in dependencies of Wikibase. The vendor directory gets committed here so that composer is not needed to actually make use the build.
