#!/usr/bin/env bash

# Ported from Jetpack's .github/files/test-plugin-update/setup.sh ("Test plugin
# upgrades"), adapted for WooCommerce: WordPress is installed on the runner and
# served via php -S, rather than the jetpack-wordpress-dev Apache container.

set -eo pipefail

# Expected environment (set by the workflow):
#   WP_PATH       Absolute path to the WordPress install / web root.
#   WP_HOST       Host the site is served from (e.g. 127.0.0.1).
#   WP_PORT       Port the site is served from (e.g. 8080).
#   DB_HOST       MariaDB host (the service container, e.g. 127.0.0.1).
#   DB_PORT       MariaDB port (e.g. 3306).
#   DB_NAME       Database name (pre-created by the service container).
#   DB_USER       Database user.
#   DB_PASS       Database password.
#   ADMIN_USER    WordPress admin username.
#   ADMIN_PASS    WordPress admin password.
#   ADMIN_EMAIL   WordPress admin email.
#   MU_PLUGIN_SRC Path to the fake-update mu-plugin to install.
#   DB_DUMP       Path to write the clean-state database dump to.
#
# The database is created by the MariaDB service container (MARIADB_DATABASE)
# and the workflow only starts steps once the service is healthy, so there is
# no need for a mysql client on the runner.

WP_URL="http://$WP_HOST:$WP_PORT"

echo "::group::Install WordPress"
mkdir -p "$WP_PATH"
cd "$WP_PATH"
wp core download
wp config create \
	--dbname="$DB_NAME" \
	--dbuser="$DB_USER" \
	--dbpass="$DB_PASS" \
	--dbhost="$DB_HOST:$DB_PORT" \
	--skip-check
# Log to debug.log (not the screen) so the test can grep it, allow in-place
# upgrades without FTP creds, and avoid loopback cron stalling php -S.
wp config set WP_DEBUG true --raw --type=constant
wp config set WP_DEBUG_LOG true --raw --type=constant
wp config set WP_DEBUG_DISPLAY false --raw --type=constant
wp config set FS_METHOD 'direct' --type=constant
wp config set DISABLE_WP_CRON true --raw --type=constant
# Disable WP's fatal-error handler (recovery mode). Otherwise a plugin that fatals
# on load gets "paused" — especially under the mu-plugin's forced-admin context —
# which would mask the fatal from the post-upgrade page-load smoke. We want fatals
# to surface (logged + HTTP 500), not be silently recovered.
wp config set WP_DISABLE_FATAL_ERROR_HANDLER true --raw --type=constant
wp core install \
	--url="$WP_URL" \
	--title="Plugin Upgrade Test" \
	--admin_user="$ADMIN_USER" \
	--admin_password="$ADMIN_PASS" \
	--admin_email="$ADMIN_EMAIL" \
	--skip-email
rm -f "$WP_PATH/index.html"
mkdir -p "$WP_PATH/wp-content/mu-plugins"
cp "$MU_PLUGIN_SRC" "$WP_PATH/wp-content/mu-plugins/plugin-upgrade-test.php"
echo "::endgroup::"

echo "::group::Verify debug.log wiring"
# Positive control: prove WordPress actually writes to the exact log file test.sh
# greps. Without this, a misconfigured WP_DEBUG_LOG would route fatals elsewhere
# and every run would pass silently.
: > "$WP_PATH/wp-content/debug.log" 2>/dev/null || true
wp eval 'error_log( "ci-debug-log-wiring-check" );'
if ! grep -q 'ci-debug-log-wiring-check' "$WP_PATH/wp-content/debug.log" 2>/dev/null; then
	echo "::error::WP_DEBUG_LOG is not routing to wp-content/debug.log; fatal detection would silently pass."
	exit 1
fi
: > "$WP_PATH/wp-content/debug.log"
echo "debug.log wiring verified."
echo "::endgroup::"

echo "::group::Backing up database"
wp db export "$DB_DUMP"
echo "::endgroup::"
