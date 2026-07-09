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
# Activate the Test Helper APIs utility plugin if not already activated.
if ! $wp_cli -- wp plugin is-active e2e-test-helpers/test-helper-apis.php >/dev/null 2>&1; then
	$wp_cli -- wp plugin activate e2e-test-helpers/test-helper-apis.php
fi

echo "Generating test translations"
node $script_dir/generate-test-translations.js
