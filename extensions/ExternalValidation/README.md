# Wikibase Quality External Validation
[![Build Status](https://travis-ci.org/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation.svg?branch=master)]
(https://travis-ci.org/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation)
[![Coverage Status](https://coveralls.io/repos/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation/badge.svg)]
(https://coveralls.io/r/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation/badges/quality-score.png?b=master)]
(https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-WikibaseQualityExternalValidation/?branch=master)

This is a complementary extension for the
[Wikibase Quality base extension](https://github.com/wikimedia/mediawiki-extensions-WikibaseQuality.git).
It performs cross checks between Wikibase and external databases to validate data.

## Installation

_If you have already installed a complementary Wikibase Quality extension you can skip the first two steps and just
add the repository (second entry in "repositories" and the required version (last entry in "require") to the
composer.local.json._  

* Create the file `composer.local.json` in the directory of your mediawiki installation.

* Add the following lines:
```
{
    "repositories": [
        {
            "type": "git",
            "url": "https://gerrit.wikimedia.org/r/mediawiki/extensions/WikibaseQuality"
        },
        {
            "type": "git",
            "url": "https://gerrit.wikimedia.org/r/mediawiki/extensions/WikibaseQualityExternalValidation"
        }
    ],
    "require": {
        "wikibase/quality": "@dev",
        "wikibase/wikibase": "@dev",
        "wikibase/external-validation": "1.x-dev"
    }
}
```

* Run `composer install`.

* If not already done, add the following lines to your `LocalSettings.php` to enable Wikibase:
```php
$wgEnableWikibaseRepo = true;
$wgEnableWikibaseClient = false;
require_once "$IP/extensions/Wikibase/repo/ExampleSettings.php";
```

* Run `php maintenance/update.php --quick`.

* Last but not least, you need to fill the tables that contain external data - for that you need the
[dump converter script](https://github.com/WikidataQuality/DumpConverter).  
Follow the instruction in the README to create a tar file (that contains a number of csv files).  
Run `php maintenance/runScript.php extensions/ExternalValidation/maintenance/UpdateExternalData.php --tar-file <path_to_tar_file>`.