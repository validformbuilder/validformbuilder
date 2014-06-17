#!/bin/bash

echo "Generating docs with PHPDocumentor..."
php phpDocumentor.phar

echo "Going home $HOME"
cd ~
#git config --global user.email "travis@travis-ci.org"
#git config --global user.name "travis-ci"
git clone --branch=gh-pages https://github.com/neverwoods/validformbuilder.git gh-pages || exit 1
echo "-----"
ls -la .
echo "-----"
echo "Entering gh-pages"
cd gh-pages
echo "-----"
ls -la .
echo "-----"
git rm -rf .
echo "-----"
ls -la .

echo "Copy generated docs from $TRAVIS_BUILD_DIR/docs/* to $HOME/gh-pages/docs"
#cp -Rf $TRAVIS_BUILD_DIR/docs/* $HOME/gh-pages/docs

echo "Current dir"
ls -la .

echo "One level deeper"
ls -la ./docs

#git add .
#git commit -F- <<EOF
#Latest docs on successful travis build $TRAVIS_BUILD_NUMBER

#ValidForm Builder commit $TRAVIS_COMMIT
#EOF
#git push origin gh-pages

echo "Published docs to gh-pages."
