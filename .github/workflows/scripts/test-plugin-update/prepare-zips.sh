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
# Add a sentinel file to the PR build so the test can prove the in-place upgrade
# actually swapped the plugin's files. zip can append a single entry to the copied
# archive, so there's no need to unpack and repack the whole plugin.
unzip -l "$DEV_ZIP_SRC" | grep -qE " $PLUGIN_SLUG/" || { echo "::error::Built zip did not contain a $PLUGIN_SLUG/ directory."; exit 1; }
cp "$DEV_ZIP_SRC" "$ZIPDIR/$PLUGIN_SLUG-dev.zip"
mkdir -p "$WORKDIR/$PLUGIN_SLUG"
touch "$WORKDIR/$PLUGIN_SLUG/ci-flag.txt"
( cd "$WORKDIR" && zip -q "$ZIPDIR/$PLUGIN_SLUG-dev.zip" "$PLUGIN_SLUG/ci-flag.txt" )
rm -rf "${WORKDIR:?}/$PLUGIN_SLUG"
echo "::endgroup::"

# The trunk (nightly) and stable (wordpress.org) builds are external, PR-independent
# sources. A fetch failure (nightly not published yet, network, rate-limit) is an
# infrastructure issue, not a PR regression, so degrade it to a warning and drop that
# source's scenarios rather than red-flagging the PR. test.sh skips a source whose zip
# is absent, and its zero-guard still fails the job if NO source ends up available.
echo "::group::Fetching $PLUGIN_SLUG-trunk.zip"
if curl -L --fail --retry 2 --retry-delay "$(( 15 + RANDOM % 8 ))" --url "$TRUNK_URL" --output "$ZIPDIR/$PLUGIN_SLUG-trunk.zip"; then
	echo "Downloaded trunk build from $TRUNK_URL"
else
	rm -f "$ZIPDIR/$PLUGIN_SLUG-trunk.zip"
	echo "::warning::Could not download the trunk build from $TRUNK_URL (is the nightly release published?); skipping trunk scenarios."
	echo "⚠️ Trunk source unavailable ($TRUNK_URL); trunk upgrade scenarios skipped — coverage reduced." >> "$GITHUB_STEP_SUMMARY"
fi
echo "::endgroup::"

echo "::group::Fetching $PLUGIN_SLUG-stable.zip"
# wordpress.org rate-limits more aggressively than GitHub, hence the longer retry delay.
# Don't use --fail on the info request: the API returns a 404 body with a valid JSON
# response when the plugin doesn't exist, which we want to inspect rather than error on.
STABLE_OK=
if JSON="$( curl -L --retry 2 --retry-delay "$(( 30 + RANDOM % 8 ))" "https://api.wordpress.org/plugins/info/1.0/$PLUGIN_SLUG.json" )" \
	&& jq -e --arg slug "$PLUGIN_SLUG" '.slug == $slug' <<<"$JSON" &>/dev/null; then
	URL="$( jq -r '.download_link // ""' <<<"$JSON" )"
	if [[ -n "$URL" ]] && curl -L --fail --retry 2 --retry-delay "$(( 30 + RANDOM % 8 ))" --url "$URL" --output "$ZIPDIR/$PLUGIN_SLUG-stable.zip"; then
		echo "Downloaded stable build from $URL"
		STABLE_OK=1
	fi
fi
if [[ -z "$STABLE_OK" ]]; then
	rm -f "$ZIPDIR/$PLUGIN_SLUG-stable.zip"
	echo "::warning::Could not fetch the stable build for $PLUGIN_SLUG from wordpress.org; skipping stable scenarios."
	echo "⚠️ Stable source unavailable (wordpress.org); stable upgrade scenarios skipped — coverage reduced." >> "$GITHUB_STEP_SUMMARY"
fi
echo "::endgroup::"
