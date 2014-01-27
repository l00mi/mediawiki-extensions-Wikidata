#! /bin/bash

rm -rf Wikibase/

git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git

cd Wikibase
git checkout -b mw1.23-wmf11 origin/mw1.23-wmf11
git submodule update --init --recursive
composer install --prefer-dist --no-dev -o
rm .gitignore
rm .gitmodules
