#!/bin/bash

set -eo pipefail

# Test Java installation
java -version

if [[ -z "$DESTINATION_PATH" ]]; then
	echo "::error::DESTINATION_PATH must be set"
	exit 1
fi

ALLURE_VERSION=2.27.0
ALLURE_DOWNLOAD_URL=https://github.com/allure-framework/allure2/releases/download/$ALLURE_VERSION/allure-$ALLURE_VERSION.zip

echo "Installing Allure $ALLURE_VERSION in $DESTINATION_PATH"
wget --no-verbose -O allure.zip $ALLURE_DOWNLOAD_URL \
  && unzip allure.zip -d "$DESTINATION_PATH" \
  && rm -rf allure.zip \

ALLURE_PATH=$(realpath "$DESTINATION_PATH"/allure-$ALLURE_VERSION/bin)

# Test Allure installation
echo "$ALLURE_PATH"
export PATH="$ALLURE_PATH:$PATH"
allure --version

# Add Allure in Github PATH to make it available to all subsequent actions in the current job
echo "$ALLURE_PATH" >> "$GITHUB_PATH"
