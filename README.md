Wikidata 'Build' Git Repository
=========

This git repo contains everything you need to deploy Wikidata (Wikibase and all of its dependencies). This repository uses git sumbodules to pull in dependencies.

## Installation

  - Pull the Git Repository
  - Run 'git submodule update'

Add the following line to your LocalSettings.php
```php
require_once("$IP/extensions/Wikidata/Wikidata.php");
```
This entry point in turn loads all other entry points

## Configuration

Wikibase itself needs to be configured, see the below links

  - https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
  - https://www.mediawiki.org/wiki/Extension:Wikibase_Client

Using this repo provides extra options to allow you to choose to deploy the Repo and/or Client.

```php
// Load the Repo Extension (default false)
$wmgUseWikibaseRepo = true;
// Load the Client Extension (default false)
$wmgUseWikibaseClient = true;
```

## Maintanence Scripts

The Maintanence scripts help within this repo will not work if you do not have the environment variable **MW_INSTALL_PATH** defined.

If you do not and can not define this variable please use the **runScript.php** maintanence script within mediawiki core (see comments in that file for instructions)
