<?php
/**
 * Plugin Name: Back in Stock Notifications — e2e test helpers
 * Description: Small utilities scoped to the BIS Playwright specs.
 *
 * Intended to function as a (mu-)plugin while tests are running. Removes the
 * 1-minute delay between a product restock and the first notifications batch,
 * so specs can assert the back-in-stock email immediately after triggering
 * `?process-waiting-actions`.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */

add_filter(
	'woocommerce_customer_stock_notifications_first_batch_delay',
	static function () {
		return 0;
	},
	PHP_INT_MAX
);
