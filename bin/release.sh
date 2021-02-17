#!/usr/bin/env bash

# Exit if any command fails
set -eo pipefail

RELEASE_VERSION=$1

FILENAME_PREFIX="Plugin_WooCommerce_"
FOLDER_PREFIX="multisafepay"
RELEASE_FOLDER=".dist"

# If tag is not supplied, latest tag is used
if [ -z "$RELEASE_VERSION" ]
then
  RELEASE_VERSION=$(git describe --tags --abbrev=0)
fi

# Remove old folder
rm -rf "$RELEASE_FOLDER"

# Create release
mkdir "$RELEASE_FOLDER"
git archive --format zip -9 --prefix="$FOLDER_PREFIX"/ --output "$RELEASE_FOLDER"/"$FILENAME_PREFIX""$RELEASE_VERSION".zip "$RELEASE_VERSION"

# Unzip for generating composer autoloader
cd "$RELEASE_FOLDER"
unzip "$FILENAME_PREFIX""$RELEASE_VERSION".zip
rm "$FILENAME_PREFIX""$RELEASE_VERSION".zip
composer install --no-dev --working-dir="$FOLDER_PREFIX"

# zip everything
zip -9 -r "$FILENAME_PREFIX""$RELEASE_VERSION".zip "$FOLDER_PREFIX" -x "$FOLDER_PREFIX""/composer.json" -x "$FOLDER_PREFIX""/composer.lock" -x "$FOLDER_PREFIX""/.wordpress-org/*" -x "$FOLDER_PREFIX""/.distignore"
