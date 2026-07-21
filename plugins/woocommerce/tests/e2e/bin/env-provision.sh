#!/usr/bin/env bash

set -euo pipefail

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
# The CI fast-path below re-runs this script inside the container with the prefix
# blanked out (WP_CLI_PREFIX=), so each command runs as a bare `wp …` in a single
# container exec instead of one `wp-env run` round-trip per command.
WP_ENV_CMD="wp-env --config .wp-env.e2e.json"
WP_CLI_PREFIX="${WP_CLI_PREFIX-$WP_ENV_CMD run cli}"

# Seeded baseline captured at the end of a full provision and restored on every
# subsequent start. It lives under the web root deliberately: that directory is a
# host bind mount which wp-env wipes whenever its own config checksum changes, so
# a missing snapshot is exactly the signal that the environment diverged and must
# be provisioned again. An .htaccess keeps the archives off the web.
SNAPSHOT_DIR='/var/www/html/.e2e-snapshot'
# This script, as mounted into the container (see `mappings` in .wp-env.e2e.json).
# Comparing it against the copy stored with the snapshot is what detects a
# changed provisioning recipe; every other input that matters (WordPress core,
# plugin zips, PHP version, ports) already moves wp-env's own checksum, which
# wipes the mount and therefore the snapshot.
MOUNTED_SCRIPT='/var/www/html/wp-content/plugins/e2e-test-bin/env-provision.sh'

if [ ! -z ${CI+y} ]; then
    # In CI we execute the setup in a single container call, while in dev
    # environments we use the script as it is. Inside the container the command is
    # executed from the /var/www/html path as pwd.
    echo -e '--> Dispatching script execution into cli\n'
    # Source from the e2e-test-bin directory mount; a single-file mount of this
    # script can surface as an empty file under Docker gRPC FUSE.
    $WP_ENV_CMD run --debug cli cp wp-content/plugins/e2e-test-bin/env-provision.sh env-provision-ci.sh
    $WP_ENV_CMD run --debug cli env -u CI WP_CLI_PREFIX= "WC_E2E_REPROVISION=${WC_E2E_REPROVISION-}" bash env-provision-ci.sh
    exit $?
fi

# Restore the seeded baseline instead of re-provisioning, unless a rebuild was
# requested or the provisioning recipe changed. This is what makes repeated
# starts fast, and it doubles as the reset: the database and uploads go back to
# the baseline on every start, so state cannot leak between test runs.
if [ -z "${WC_E2E_REPROVISION-}" ] &&
	$WP_CLI_PREFIX bash -c "[ -f '$SNAPSHOT_DIR/db.sql' ] && cmp -s '$MOUNTED_SCRIPT' '$SNAPSHOT_DIR/provision.sh'"; then
	echo -e 'Restoring the seeded baseline \n'
	$WP_CLI_PREFIX bash -c "
		set -e
		wp db reset --yes
		wp db import '$SNAPSHOT_DIR/db.sql'
		rm -rf /var/www/html/wp-content/uploads
		tar -C /var/www/html/wp-content -xf '$SNAPSHOT_DIR/uploads.tar'
	"
	echo -e 'Baseline restored \n'
	exit 0
fi

echo -e 'Provisioning a fresh baseline \n'

# In nightly runs WooCommerce is mounted via a wp-env mapping so it installs
# under the canonical `woocommerce` folder; mapped plugins are not
# auto-activated, so activate it before any WC-dependent setup below (e.g. the
# `customer` role user). Harmless when WC is already active (PR/source-mapped).
echo -e 'Activate WooCommerce \n'
$WP_CLI_PREFIX wp plugin activate woocommerce

echo -e 'Install twentytwenty, twentytwentytwo and storefront themes \n'
$WP_CLI_PREFIX wp theme install storefront twentytwenty twentytwentytwo &
theme_install_pid=$!

echo -e 'Activate default theme \n'
$WP_CLI_PREFIX wp theme activate twentytwentythree

# Provision wp-cli.yml in-container instead of mapping it. Single-file Docker
# mounts can surface as empty files under gRPC FUSE, which would silently drop
# the apache_modules declaration that `wp rewrite ... --hard` needs to write the
# mod_rewrite block to .htaccess.
echo -e 'Provision wp-cli.yml \n'
$WP_CLI_PREFIX bash -c 'printf "apache_modules:\n  - mod_rewrite\n" > /var/www/html/wp-cli.yml'

echo -e 'Update URL structure \n'
$WP_CLI_PREFIX wp rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
if ! $WP_CLI_PREFIX wp user get customer --field=ID >/dev/null 2>&1; then
	$WP_CLI_PREFIX wp user create customer customer@woocommercecoree2etestsuite.com \
		--user_pass=password \
		--role=customer \
		--first_name='Jane' \
		--last_name='Smith' \
		--user_registered='2022-01-01 12:23:45'
fi

echo -e 'Update Blog Name \n'
$WP_CLI_PREFIX wp option update blogname 'WooCommerce Core E2E Test Suite'

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

if [ $ENABLE_TRACKING == 1 ]; then
	echo -e 'Enable tracking\n'
	$WP_CLI_PREFIX wp option update woocommerce_allow_tracking 'yes'
fi

echo -e 'Wait for theme install to finish \n'
wait "$theme_install_pid"

# Re-imported from scratch rather than skipped when present: this hook runs on
# every start, and the database outlives the web root (wp-env's self-heal wipes
# the bind mount but keeps the MySQL volume), so attachment rows can survive
# without their files. Dropping just these three and re-importing is idempotent
# whichever way the two have drifted. Other attachments — notably WooCommerce's
# own placeholder — are left alone.
echo -e 'Upload test images \n'
$WP_CLI_PREFIX bash -c "
	set -e
	for slug in image-01 image-02 image-03; do
		ids=\$(wp post list --post_type=attachment --name=\"\$slug\" --format=ids)
		if [ -n \"\$ids\" ]; then
			wp post delete \$ids --force >/dev/null
		fi
	done
	wp media import ./test-data/images/image-01.png ./test-data/images/image-02.png ./test-data/images/image-03.png
"

# Capture the baseline for subsequent starts to restore, together with the copy
# of this script used to detect a changed provisioning recipe.
echo -e 'Capturing the seeded baseline \n'
$WP_CLI_PREFIX bash -c "
	set -e
	mkdir -p '$SNAPSHOT_DIR'
	printf 'Require all denied\n' > '$SNAPSHOT_DIR/.htaccess'
	wp db export '$SNAPSHOT_DIR/db.sql'
	tar -C /var/www/html/wp-content -cf '$SNAPSHOT_DIR/uploads.tar' uploads
	cp '$MOUNTED_SCRIPT' '$SNAPSHOT_DIR/provision.sh'
"
