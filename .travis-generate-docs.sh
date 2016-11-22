#!/bin/bash

set -e # Exit with nonzero exit code if anything fails

SOURCE_BRANCH="master"
TARGET_BRANCH="gh-pages"

# Pull requests and commits to other branches shouldn't try to deploy, just build to verify
#if [ "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "$SOURCE_BRANCH" ]; then
#    echo "Skipping documentation generation; just doing a build."
#    exit 0
#fi

echo "Generating docs with PHPDocumentor..."
phpdoc -c phpdoc.xml --visibility=public # Since we've added vendor/bin to the PATH variable, we can just execute phpdoc now.

echo "Going home $HOME"
cd ~
git config --global user.email "bili@neverwoods.com"
git config --global user.name "Bili (Travis CI)"

git clone --branch=${TARGET_BRANCH} https://github.com/neverwoods/validformbuilder.git ${TARGET_BRANCH}

echo "Entering ${TARGET_BRANCH}"
cd ${TARGET_BRANCH}/docs

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

git push https://${GH_TOKEN}:@github.com/neverwoods/validformbuilder.git HEAD:${TARGET_BRANCH} > /dev/null 2>&1 || exit 1
echo "Published docs to ${TARGET_BRANCH}."
