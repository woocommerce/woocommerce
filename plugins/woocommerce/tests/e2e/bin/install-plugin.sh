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

# Resolve the latest release asset through github.com's redirect instead of api.github.com:
# the unauthenticated API allows only 60 requests/hour per IP, which shared runners exhaust.
asset="${PLUGIN_ASSET:-$PLUGIN_SLUG.zip}"
latest_url="https://github.com/$PLUGIN_REPOSITORY/releases/latest/download/$asset"
curl_exit=0
curl_result=$( curl -sS -o /dev/null --retry 3 --retry-all-errors --retry-delay 2 -w '%{http_code} %{redirect_url}' "$latest_url" ) || curl_exit=$?
http_code="${curl_result%% *}"
download_url="${curl_result#* }"
if [[ "$curl_exit" -ne 0 || -z "$download_url" ]]; then
	echo "::error::Could not resolve the latest $PLUGIN_NAME release asset '$asset' from $PLUGIN_REPOSITORY (curl exit $curl_exit, HTTP ${http_code:-none}, $latest_url)"
	exit 1
fi
echo "Resolved $PLUGIN_NAME download: $download_url"

# Since WordPress 7.1.4, a zip with no top-level folder installs into a folder with a random suffix.
# Rename it to $PLUGIN_SLUG. WP-CLI 2.12.0 bundles extension-command 2.1.24; switch to `wp plugin install --slug` once WP-CLI bundles extension-command 2.3.0+.
rename_to_slug="WP_CLI::add_wp_hook( 'upgrader_source_selection', function ( \$source ) { if ( basename( \$source ) === '$PLUGIN_SLUG' ) { return \$source; } \$target = dirname( \$source ) . '/$PLUGIN_SLUG/'; return \$GLOBALS['wp_filesystem']->move( \$source, \$target, true ) ? \$target : new WP_Error( 'rename_failed', 'Could not rename the plugin folder to $PLUGIN_SLUG.' ); } );"
install_cmd=( wp plugin install "$download_url" --force --activate "--exec=$rename_to_slug" )
$wp_cli -- "${install_cmd[@]}" || {
	echo "::warning::Installing $PLUGIN_NAME from $download_url failed, retrying in 5 seconds"
	sleep 5
	$wp_cli -- "${install_cmd[@]}" || { echo "::error::Could not install $PLUGIN_NAME from $download_url"; exit 1; }
}

$wp_cli wp plugin list
$wp_cli wp plugin is-active "$PLUGIN_SLUG" || { echo "::error::Plugin \"$PLUGIN_SLUG\" is not active after installing $PLUGIN_NAME from $download_url"; exit 1; }
