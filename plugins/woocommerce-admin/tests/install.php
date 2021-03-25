<?php
/**
 * Install tests
 *
 * @package WooCommerce\Admin\Tests
 */

use \Automattic\WooCommerce\Admin\Install;

/**
 * Tests for \Automattic\WooCommerce\Admin\Install class.
 */
class WC_Admin_Tests_Install extends WP_UnitTestCase {

	/**
	 * Integration test for database table creation.
	 *
	 * @group database
	 */
	function test_create_tables() {
		global $wpdb;

		// List of tables created by Install::test_create_tables.
		$tables = array(
			"{$wpdb->prefix}wc_order_stats",
			"{$wpdb->prefix}wc_order_product_lookup",
			"{$wpdb->prefix}wc_order_tax_lookup",
			"{$wpdb->prefix}wc_order_coupon_lookup",
			"{$wpdb->prefix}wc_admin_notes",
			"{$wpdb->prefix}wc_admin_note_actions",
			"{$wpdb->prefix}wc_customer_lookup",
			"{$wpdb->prefix}wc_category_lookup"
		);

		// Remove any existing tables in the environment.
		$query = 'DROP TABLES IF EXISTS' . implode( ',', $tables );
		$wpdb->query( $query );

		// Try to create the tables.
		Install::create_tables();
		$result = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

		// Check all the tables exist.
		foreach ( $tables as $table ) {
			$this->assertContains( $table, $result );
		}
	}
}
