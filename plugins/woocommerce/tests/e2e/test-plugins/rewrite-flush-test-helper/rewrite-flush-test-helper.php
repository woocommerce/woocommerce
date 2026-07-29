<?php
/**
 * Plugin Name: Rewrite Flush Test Helper
 * Description: Flushes rewrite rules from a scheduled E2E test event.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: WooCommerce
 *
 * @package Rewrite_Flush_Test_Helper
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'rewrite_flush_test_helper_flush',
	static function () {
		flush_rewrite_rules();
	}
);
