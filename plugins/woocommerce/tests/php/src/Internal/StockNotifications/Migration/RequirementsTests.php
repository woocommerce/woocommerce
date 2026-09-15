<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the checks a run makes before it starts and on every batch.
 */
class RequirementsTests extends WC_Unit_Test_Case {

	/**
	 * Requirements under test.
	 *
	 * @var Requirements
	 */
	private Requirements $requirements;

	/**
	 * Set up the legacy tables and the feature toggle.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		LegacyStore::create_tables();
		LegacyStore::truncate_all();

		$this->requirements = new Requirements();
		$this->requirements->init( wc_get_container()->get( StockNotificationsDataStore::class ) );
	}

	/**
	 * Drop the legacy tables and the toggle.
	 */
	public function tearDown(): void {
		LegacyStore::drop_tables();
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );

		parent::tearDown();
	}

	/**
	 * @testdox every requirement met should pass.
	 */
	public function test_all_requirements_met(): void {
		$this->assertTrue( $this->requirements->check() );
	}

	/**
	 * @testdox the feature being off should fail with Features-screen guidance, not a fatal.
	 */
	public function test_feature_off_fails_with_guidance(): void {
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$result = $this->requirements->check();

		$this->assertWPError( $result );
		$this->assertSame( 'feature_disabled', $result->get_error_code() );
		$this->assertStringContainsString( 'Features', $result->get_error_message() );
	}

	/**
	 * @testdox missing legacy tables should fail and name the table.
	 */
	public function test_missing_legacy_tables_fail_and_name_the_table(): void {
		global $wpdb;

		LegacyStore::drop_tables();

		$result = $this->requirements->check();

		$this->assertWPError( $result );
		$this->assertSame( 'legacy_tables_missing', $result->get_error_code() );
		$this->assertStringContainsString( $wpdb->prefix . 'woocommerce_bis_notifications', $result->get_error_message() );
	}

	/**
	 * @testdox a similarly named table should not make an existing legacy table look missing.
	 */
	public function test_a_colliding_table_name_does_not_hide_an_existing_table(): void {
		global $wpdb;

		// `SHOW TABLES LIKE` treats `_` as a wildcard, and this name sorts before the real
		// one, so an unescaped pattern gets this back as the first match.
		$collider = $wpdb->prefix . 'woocommerce_bisXnotifications';
		$wpdb->query( "CREATE TABLE {$collider} ( id BIGINT UNSIGNED NOT NULL )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- fixture table built from $wpdb->prefix, never user input.

		try {
			$this->assertTrue( $this->requirements->check() );
		} finally {
			$wpdb->query( "DROP TABLE IF EXISTS {$collider}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- fixture cleanup.
		}
	}

	/**
	 * @testdox the queued-row count should be zero while the legacy extension is inactive.
	 */
	public function test_queued_rows_are_ignored_without_the_legacy_extension(): void {
		$product = new \WC_Product_Simple();
		$product->save();

		LegacyStore::add_notification(
			array(
				'product_id' => $product->get_id(),
				'is_queued'  => 'on',
			)
		);

		$this->assertFalse( class_exists( 'WC_Back_In_Stock' ), 'This test assumes the legacy extension is not loaded.' );
		$this->assertSame( 0, $this->requirements->count_legacy_queued_rows(), 'Nothing will ever drain that queue.' );
	}
}
