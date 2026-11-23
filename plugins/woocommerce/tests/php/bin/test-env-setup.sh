#!/usr/bin/env bash

# Lifecycle setup for the lean PHP-unit wp-env (.wp-env.test.json). Themes come from
# the config's `themes` array; the only thing config cannot express is the CSV
# fixture that WC_Tests_Product_CSV_Importer::test_server_path_traversal reads from
# `/var/www/sample.csv` (above ABSPATH, where www-data cannot write, so copy as root).
WP_ENV_TEST_CMD="wp-env --config ${WC_TEST_ENV_CONFIG:-.wp-env.test.json}"
WP_CLI_PREFIX="${WP_CLI_PREFIX-$WP_ENV_TEST_CMD run cli}"

# The test bootstrap loads includes/react-admin/feature-config.php, which the build
# generates. A checkout that was never built misses it and every test stops at bootstrap.
PLUGIN_DIR="$(cd "$(dirname "$0")/../../.." && pwd)"
if [ ! -f "$PLUGIN_DIR/includes/react-admin/feature-config.php" ] && command -v php >/dev/null 2>&1; then
	echo -e 'Generate the admin feature config \n'
	php "$PLUGIN_DIR/bin/generate-feature-config.php"
fi

echo -e 'Pre-place sample.csv fixture \n'
$WP_CLI_PREFIX sudo cp /var/www/html/wp-content/plugins/woocommerce/tests/legacy/unit-tests/importer/sample.csv /var/www/sample.csv
