#Wikidata Build

Wikidata is using a build with Wikibase and its dependencies packaged into one git repo.

If you update the master branch of this repo it will likely be overwritten in the next 24 hours. To change things please see [here](https://github.com/wmde/WikidataBuildResources).

## Installation

1. Clone the git repo into the [mediawiki extensions directory](https://git.wikimedia.org/summary/mediawiki%2Fextensions%2FWikidata)
2. Add the following line to your LocalSettings.php. This entry point in turn loads all other entry points.

##### To enable Repo add the following

```php
$wgEnableWikibaseRepo = true;
$wgEnableWikibaseClient = false;
$wmgUseWikibaseRepo = true;
$wmgUseWikibaseClient = false;
require_once __DIR__ . "/extensions/Wikidata/Wikidata.php";
require_once __DIR__ . "/extensions/Wikidata/extensions/Wikibase/repo/ExampleSettings.php";
```

##### To enable Client add the following

```php
$wgEnableWikibaseRepo = false;
$wgEnableWikibaseClient = true;
$wmgUseWikibaseRepo = false;
$wmgUseWikibaseClient = true;
require_once __DIR__ . "/extensions/Wikidata/Wikidata.php";
```

## Configuration

Wikibase itself needs to be configured, with appropriate settings. See the below links:

* [Extension:Wikibase Repository](https://www.mediawiki.org/wiki/Extension:Wikibase_Repository)
* [Extension:Wikibase Client](https://www.mediawiki.org/wiki/Extension:Wikibase_Client)

Using this repo provides extra options to allow you to choose to deploy the Repo and/or Client.

```php
// Load the Repo Extension (default false)
$wmgUseWikibaseRepo = true;
// Load the Client Extension (default false)
$wmgUseWikibaseClient = true;
```

## Maintenance scripts

The Maintenance scripts help within this repo will not work if you do not have the environment variable **MW_INSTALL_PATH** defined.

If you do not and can not define this variable please use the **runScript.php** maintenance script within mediawiki core (see comments in that file for instructions).

## Manually update a build

Manually updating a Wikidata build requires [composer](http://getcomposer.org/) to be installed on the system.

The Wikidata build uses grunt (node.js) to provide some convenience functions. To use this, do:

```bash

npm install
```

Then to make a build, run:

```bash

grunt install
```

To clean the install (remove composer.lock, /vendor and /extensions):

```bash

grunt uninstall
```

To make a deployment build, using appropriate branchName argument:

```bash

grunt branch --branchName="wmf/1.25wmf1"
```

For deployment builds, this sets the autoloader-suffix config option in the composer.json to be based on the branch name, which makes the composer autoloader code more stable when deployed and synced to multiple servers.

The sharedCacheKeyPrefix setting is also set to be based on the branch version, and can be manually changed when doing backports to a deployment branch, as needed for invalidating memcached entries.

## Updating this README

This readme is located [here](https://github.com/wmde/WikidataBuildResources/blob/master/README.md). Any changes made to the README in the Wikidata build repo will be overwritten by this file daily.
