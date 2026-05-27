#!/usr/bin/env bash

# Ported from Jetpack's .github/files/test-plugin-update/prepare-zips.sh ("Test
# plugin upgrades"), simplified for WooCommerce: a single plugin, with trunk
# pulled from the nightly GitHub release and stable from the wordpress.org API
# (no betadownload manifest, no multi-plugin matrix).

set -eo pipefail

# Expected environment (set by the workflow):
#   PLUGIN_SLUG  Plugin slug / install directory name (woocommerce).
#   DEV_ZIP_SRC  Path to the built PR zip (internal dir: <slug>/).
#   ZIPDIR       Output directory for the prepared upgrade-source zips.
#   WORKDIR      Scratch directory.
#   TRUNK_URL    URL of the latest trunk (nightly) build, e.g.
#                https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip

mkdir -p "$ZIPDIR" "$WORKDIR"

echo "::group::Creating $PLUGIN_SLUG-dev.zip"
# Unpack the PR build and add a sentinel file, so the test can prove the
# in-place upgrade actually swapped the plugin's files.
unzip -q "$DEV_ZIP_SRC" -d "$WORKDIR"
[[ -d "$WORKDIR/$PLUGIN_SLUG" ]] || { echo "::error::Built zip did not contain a $PLUGIN_SLUG/ directory."; exit 1; }
touch "$WORKDIR/$PLUGIN_SLUG/ci-flag.txt"
( cd "$WORKDIR" && zip -qr "$ZIPDIR/$PLUGIN_SLUG-dev.zip" "$PLUGIN_SLUG" )
rm -rf "${WORKDIR:?}/$PLUGIN_SLUG"
echo "::endgroup::"

echo "::group::Fetching $PLUGIN_SLUG-trunk.zip"
if curl -L --fail --retry 2 --retry-delay "$(( 15 + RANDOM % 8 ))" --url "$TRUNK_URL" --output "$ZIPDIR/$PLUGIN_SLUG-trunk.zip"; then
	echo "Downloaded trunk build from $TRUNK_URL"
else
	echo "::error::Failed to download trunk build from $TRUNK_URL (is the nightly release published?)"
	echo "❌ Failed to download trunk build for $PLUGIN_SLUG" >> "$GITHUB_STEP_SUMMARY"
	exit 1
fi
echo "::endgroup::"

echo "::group::Fetching $PLUGIN_SLUG-stable.zip"
# wordpress.org rate-limits more aggressively than GitHub, hence the longer retry delay.
# Don't use --fail on the info request: the API returns a 404 body with a valid JSON
# response when the plugin doesn't exist, which we want to inspect rather than error on.
if ! JSON="$( curl -L --retry 2 --retry-delay "$(( 30 + RANDOM % 8 ))" "https://api.wordpress.org/plugins/info/1.0/$PLUGIN_SLUG.json" )"; then
	echo "::error::Request to the WordPress.org API for $PLUGIN_SLUG failed."
	echo "❌ Request to the WordPress.org API for $PLUGIN_SLUG failed" >> "$GITHUB_STEP_SUMMARY"
	exit 1
fi
if jq -e --arg slug "$PLUGIN_SLUG" '.slug == $slug' <<<"$JSON" &>/dev/null; then
	URL="$( jq -r '.download_link // ""' <<<"$JSON" )"
	if [[ -z "$URL" ]]; then
		echo "::error::$PLUGIN_SLUG has no stable release download_link."
		echo "❌ No stable release found for $PLUGIN_SLUG" >> "$GITHUB_STEP_SUMMARY"
		exit 1
	fi
	if curl -L --fail --retry 2 --retry-delay "$(( 30 + RANDOM % 8 ))" --url "$URL" --output "$ZIPDIR/$PLUGIN_SLUG-stable.zip"; then
		echo "Downloaded stable build from $URL"
	else
		echo "::error::Failed to download stable build from $URL"
		echo "❌ Failed to download stable build for $PLUGIN_SLUG" >> "$GITHUB_STEP_SUMMARY"
		exit 1
	fi
else
	echo "::error::Unexpected response from the WordPress.org API for $PLUGIN_SLUG"
	echo "$JSON"
	echo "❌ Unexpected response from the WordPress.org API for $PLUGIN_SLUG" >> "$GITHUB_STEP_SUMMARY"
	exit 1
fi
echo "::endgroup::"
