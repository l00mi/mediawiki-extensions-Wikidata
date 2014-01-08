#! /bin/bash

rm -rf Wikibase/

# https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git
git clone https://github.com/wmde/Wikibase.git

cd Wikibase
git checkout newcomponents
composer install --prefer-dist --no-dev
rm .gitignore