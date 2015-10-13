#! /bin/bash

set -x

cd ../wiki/tests/phpunit
php phpunit.php -c ../../extensions/WikibaseQuality/phpunit.xml.dist