#!/usr/bin/env bash

# Exit if any command fails
set -eo pipefail

RELEASE_VERSION=$1

FILENAME_PREFIX="Plugin_WooCommerce_"
FOLDER_PREFIX="multisafepay"
RELEASE_FOLDER=".release"

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

# Install Node Modules
cd "$FOLDER_PREFIX"
npm install --include=dev --no-fund

# Build file.
npm run build

# Remove node_modules
rm -rf node_modules

# Remove src folder
rm -rf assets/public/js/multisafepay-blocks/src
