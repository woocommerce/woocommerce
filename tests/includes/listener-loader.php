<?php
/**
 * Listener loader.
 *
 * @package WooCommerce\UnitTests
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : sys_get_temp_dir() . '/wordpress-tests-lib';
require_once $wp_tests_dir . '/includes/listener-loader.php';
