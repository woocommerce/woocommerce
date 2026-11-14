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

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
wp_cli="pnpm wp-env:e2e run cli"

echo "Installing $PLUGIN_NAME from $PLUGIN_REPOSITORY"
download_url=$( curl -s "https://api.github.com/repos/$PLUGIN_REPOSITORY/releases/latest" | grep browser_download_url | cut -d '"' -f 4 )
# Since WordPress 7.1.4, a zip with no top-level folder installs into a folder with a random suffix.
# Rename it to $PLUGIN_SLUG. WP-CLI 2.12.0 bundles extension-command 2.1.24; switch to `wp plugin install --slug` once WP-CLI bundles extension-command 2.3.0+.
rename_to_slug="WP_CLI::add_wp_hook( 'upgrader_source_selection', function ( \$source ) { if ( basename( \$source ) === '$PLUGIN_SLUG' ) { return \$source; } \$target = dirname( \$source ) . '/$PLUGIN_SLUG/'; return \$GLOBALS['wp_filesystem']->move( \$source, \$target, true ) ? \$target : new WP_Error( 'rename_failed', 'Could not rename the plugin folder to $PLUGIN_SLUG.' ); } );"
install_cmd=( wp plugin install "$download_url" --force --activate "--exec=$rename_to_slug" )
$wp_cli -- "${install_cmd[@]}" || ( sleep 5 && $wp_cli -- "${install_cmd[@]}" )

$wp_cli wp plugin list
$wp_cli wp plugin is-active "$PLUGIN_SLUG" || ( echo "Plugin \"$PLUGIN_SLUG\" is not active!" && exit 1 )
