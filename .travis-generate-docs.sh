#!/bin/bash

echo "Generating docs with PHPDocumentor..."
php phpDocumentor.phar

echo "Going home $HOME"
cd ~
git config --global user.email "robin@trainedby.ninja"
git config --global user.name "Travis CI"
git clone --branch=gh-pages https://${GH_TOKEN}:github.com/neverwoods/validformbuilder.git gh-pages > /dev/null 2>&1 || exit 1

echo "Entering gh-pages"
cd gh-pages/docs

git rm -r **/*
touch placeholder #adding a placeholder keeps the docs folder
git rm *.*

cd ..

echo "Copy generated docs from $TRAVIS_BUILD_DIR/docs/* to ./docs"
cp -Rf $TRAVIS_BUILD_DIR/docs/* ./docs

rm docs/placeholder #docs is filled, no need for placeholder anymore

git add --all
git commit -F- <<EOF
Latest docs on successful travis build $TRAVIS_BUILD_NUMBER
ValidForm Builder commit $TRAVIS_COMMIT
EOF

git push origin gh-pages
echo "Published docs to gh-pages."
