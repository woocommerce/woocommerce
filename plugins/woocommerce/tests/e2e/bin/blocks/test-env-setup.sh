#!/usr/bin/env bash
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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

# Ensure the E2E test helper is active before the blocks suite exports its database
# snapshot (the snapshot is restored between every test, so whatever is active here is
# what the specs see). Several blocks specs call its REST routes — e2e-feature-flags,
# e2e-options, e2e-theme — and fail with `rest_no_route` if it is not active. The core
# E2E env gets this via the .wp-env.e2e.json "plugins" array, but the blocks snapshot
# needs it pinned explicitly here.
if ! $wp_cli -- wp plugin is-active woocommerce-e2e-test-helper >/dev/null 2>&1; then
	$wp_cli -- wp plugin activate woocommerce-e2e-test-helper
fi

echo "Generating test translations"
node $script_dir/generate-test-translations.js
