#!/usr/bin/env bash

# The steps after the seed are as easy to lose as the steps inside it: a
# silently skipped preference write or translation build is restored before
# every test along with everything else. Fail on the first broken one.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
wp_cli="wp-env --config .wp-env.e2e.json run cli"

# Remove the database snapshot if it exists.
$wp_cli -- rm -f blocks_e2e.sql
# Run the main script in the container for better performance.
$wp_cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh

# One container round-trip: each `wp-env run` costs a Node start plus a
# `docker compose exec`. The `&&` chain keeps the required order -- the lock
# file must exist before the prepend is installed, and the prepend must be
# installed before the probe can pass.
echo "Configuring database request lock"
$wp_cli -- bash -c 'touch /var/www/html/.woocommerce-blocks-e2e-db.lock && chmod 0666 /var/www/html/.woocommerce-blocks-e2e-db.lock && test -f /var/www/html/.woocommerce-blocks-e2e-db.lock && test -r /var/www/html/.woocommerce-blocks-e2e-db.lock && test -w /var/www/html/.woocommerce-blocks-e2e-db.lock && php wp-content/plugins/woocommerce/blocks-bin/playwright/htaccess-lock-block.php install && curl --fail --silent --show-error http://wordpress/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock-probe.php'

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
