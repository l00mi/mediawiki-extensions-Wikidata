#! /bin/bash

set -x

cd ../wiki/tests/phpunit
php phpunit.php -c ../../extensions/WikibaseQualityExternalValidation/phpunit.xml.dist