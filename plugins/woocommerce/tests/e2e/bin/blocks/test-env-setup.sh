#!/usr/bin/env bash

# The steps after the seed are as easy to lose as the steps inside it: a
# silently skipped preference write or translation build is restored before
# every test along with everything else. Fail on the first broken one.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

###################################################################################################
# TEMPORARY SABOTAGE -- DO NOT MERGE. Delete this block before this branch goes anywhere.
###################################################################################################
# Forces the job's first `wp-env start` to fail here, after the containers are
# already running, which is the one condition the retry in ci.yml could not
# recover from. The marker lives in the runner's temp directory and survives
# between attempts within the job, so attempt 2 gets past this block and the job
# then shows whether the retry recovers. The message is the real seed guard's,
# so the workflow's reason= classifier is exercised as well.
forced_failure_marker="${RUNNER_TEMP:-/tmp}/wp-env-forced-first-failure"
if [ ! -f "$forced_failure_marker" ]; then
	: > "$forced_failure_marker"
	echo "Missing gallery attachment; the sample-data image import did not complete." >&2
	exit 1
fi
###################################################################################################
# END TEMPORARY SABOTAGE
###################################################################################################

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
wp_cli="wp-env --config .wp-env.e2e.json run cli"

# Remove the database snapshot if it exists.
$wp_cli -- rm -f blocks_e2e.sql
# Run the main script in the container for better performance.
$wp_cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh
# Disable the LYS Coming Soon banner.
$wp_cli -- wp option update woocommerce_coming_soon 'no'
# Dismiss the site editor welcome guide for the admin user so it does not
# block interactions during tests. The preference is stored in user meta and
# will be included in the database snapshot that is restored between tests.
$wp_cli -- wp eval '
$prefs = get_user_meta( 1, "wp_persisted_preferences", true );
if ( ! is_array( $prefs ) ) { $prefs = array(); }
if ( ! isset( $prefs["core/edit-site"] ) ) { $prefs["core/edit-site"] = array(); }
$prefs["core/edit-site"]["welcomeGuide"] = false;
$prefs["core/edit-site"]["welcomeGuideStyles"] = false;
$prefs["core/edit-site"]["welcomeGuidePage"] = false;
$prefs["core/edit-site"]["welcomeGuideTemplate"] = false;
update_user_meta( 1, "wp_persisted_preferences", $prefs );
'

echo "Generating test translations"
node $script_dir/generate-test-translations.js
