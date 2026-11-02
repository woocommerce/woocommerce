#!/usr/bin/env bash

# Lifecycle setup for the lean PHP-unit wp-env (.wp-env.test.json). Themes come from
# the config's `themes` array; the only thing config cannot express is the CSV
# fixture that WC_Tests_Product_CSV_Importer::test_server_path_traversal reads from
# `/var/www/sample.csv` (above ABSPATH, where www-data cannot write, so copy as root).
WP_ENV_TEST_CMD="wp-env --config .wp-env.test.json"
WP_CLI_PREFIX="${WP_CLI_PREFIX-$WP_ENV_TEST_CMD run cli}"

echo -e 'Pre-place sample.csv fixture \n'
$WP_CLI_PREFIX sudo cp /var/www/html/wp-content/plugins/woocommerce/tests/legacy/unit-tests/importer/sample.csv /var/www/sample.csv
