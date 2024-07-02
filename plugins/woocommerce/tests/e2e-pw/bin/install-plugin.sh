#!/bin/bash

set -eo pipefail

if [[ -z "$PLUGIN_REPOSITORY" ]]; then
	echo "::error::PLUGIN_REPOSITORY must be set"
	exit 1
fi

if [[ -z "$PLUGIN_NAME" ]]; then
	echo "::error::PLUGIN_NAME must be set"
	exit 1
fi

if [[ -z "$PLUGIN_SLUG" ]]; then
	echo "::error::PLUGIN_SLUG must be set"
	exit 1
fi


echo "Installing $PLUGIN_NAME from $PLUGIN_REPOSITORY"

echo "Uninstalling plugin if it's already installed..."
pnpm wp-env run tests-cli wp plugin uninstall "$PLUGIN_SLUG" --deactivate || true

echo "Downloading plugin..."
download_url=$(curl -s "https://api.github.com/repos/$PLUGIN_REPOSITORY/releases/latest" | grep browser_download_url | cut -d '"' -f 4)
pnpm wp-env run tests-cli wp plugin install "$download_url" --activate

pnpm wp-env run tests-cli wp plugin list
pnpm wp-env run tests-cli wp plugin is-active "$PLUGIN_SLUG" || ( echo "Plugin \"$PLUGIN_SLUG\" is not active!" && exit 1 )
