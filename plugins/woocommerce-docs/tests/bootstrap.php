<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
/**
 * PHPUnit bootstrap file
 *
 * @package WooCommerce_Docs
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$tests_dir = getenv( 'WP_TESTS_DIR' );

require_once $tests_dir . '/includes/functions.php';
require_once $tests_dir . '/includes/bootstrap.php';

// Require action-scheduler manually.
require_once __DIR__ . '/../vendor/woocommerce/action-scheduler/action-scheduler.php';
