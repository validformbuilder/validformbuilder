#!/bin/bash

echo "Generating docs with PHPDocumentor..."
php phpDocumentor.phar

echo "Going home $HOME"
cd "$HOME"
#git config --global user.email "travis@travis-ci.org"
#git config --global user.name "travis-ci"
git clone --branch=gh-pages https://github.com/neverwoods/validformbuilder.git gh-pages || exit 1

echo "Entering gh-pages/docs"
cd gh-pages/docs
git rm -rf .

echo "Copy generated docs from $TRAVIS_BUILD_DIR/docs/* to ./"
cp -rf "$TRAVIS_BUILD_DIR"/docs/* ./ || exit 1

git add .
git commit -F- <<EOF
Latest docs on successful travis build $TRAVIS_BUILD_NUMBER

ValidForm Builder commit $TRAVIS_COMMIT
EOF
git push origin gh-pages

echo "Published docs to gh-pages."
