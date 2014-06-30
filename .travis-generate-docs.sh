#!/bin/bash

echo "Generating docs with PHPDocumentor..."
phpdoc -c phpdoc.xml --visibility=public # Since we've added vendor/bin to the PATH variable, we can just execute phpdoc now.

echo "Going home $HOME"
cd ~
git config --global user.email "robin@trainedby.ninja"
git config --global user.name "Travis CI"

git clone --branch=gh-pages https://github.com/neverwoods/validformbuilder.git gh-pages

echo "Entering gh-pages"
cd gh-pages/docs

git rm -r **/*
touch placeholder #adding a placeholder keeps the docs folder
git rm *.*

cd ..

echo "Copy generated docs from $TRAVIS_BUILD_DIR/docs/* to ./docs"
cp -Rf $TRAVIS_BUILD_DIR/docs/* ./docs

rm docs/placeholder #docs is filled, no need for placeholder anymore

# Add custom stylesheet

echo "Append custom stylesheet ./stylesheets/docs.css to default stylesheet ./docs/css/template.css"
ls -l
cat ./stylesheets/docs.css >> ./docs/css/template.css

git add --all
git commit -F- <<EOF
Latest docs on successful travis build $TRAVIS_BUILD_NUMBER
ValidForm Builder commit $TRAVIS_COMMIT
EOF

git push https://${GH_TOKEN}:@github.com/neverwoods/validformbuilder.git HEAD:gh-pages > /dev/null 2>&1 || exit 1
echo "Published docs to gh-pages."
