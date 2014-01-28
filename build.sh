#! /bin/bash
branch=mw1.23-wmf11

rm -rf Wikibase/

git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git -b $branch --depth 1

cd Wikibase
git submodule update --init --recursive
composer install --prefer-dist --no-dev -o
rm .gitignore
rm .gitmodules
