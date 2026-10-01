<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrderModifiedDateUpdater;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderModifiedDateUpdater class.
 */
class OrderModifiedDateUpdaterTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	private const OLD_DATE_GMT = '2020-01-01 00:00:00';

	/**
	 * The System Under Test.
	 *
	 * @var OrderModifiedDateUpdater
	 */
	private $sut;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->sut = wc_get_container()->get( OrderModifiedDateUpdater::class );
	}

	/**
	 * @testdox Updates only the posts table when posts are authoritative and sync is disabled.
	 */
	public function test_updates_posts_table_when_posts_are_authoritative(): void {
		$this->toggle_cot_authoritative( false );
		$this->disable_cot_sync();
		$order_id = OrderHelper::create_order()->get_id();
		$this->set_old_modified_dates( $order_id );

		$this->sut->update_modified_date( $order_id );

		$this->assertNotSame( self::OLD_DATE_GMT, $this->get_post_modified_gmt( $order_id ), 'The post modified date should be updated.' );
		$this->assertGreaterThan( strtotime( self::OLD_DATE_GMT ), wc_get_order( $order_id )->get_date_modified()->getTimestamp(), 'The loaded order should have the new modified date.' );
	}

	/**
	 * @testdox Updates only the orders table when HPOS is authoritative and sync is disabled.
	 */
	public function test_updates_orders_table_when_hpos_is_authoritative(): void {
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();
		$order_id = OrderHelper::create_order()->get_id();
		$this->set_old_modified_dates( $order_id );

		$this->sut->update_modified_date( $order_id );

		$this->assertNotSame( self::OLD_DATE_GMT, $this->get_orders_table_date_updated_gmt( $order_id ), 'The orders table modified date should be updated.' );
		$this->assertSame( self::OLD_DATE_GMT, $this->get_post_modified_gmt( $order_id ), 'The placeholder post should not be touched.' );
		$this->assertGreaterThan( strtotime( self::OLD_DATE_GMT ), wc_get_order( $order_id )->get_date_modified()->getTimestamp(), 'The loaded order should have the new modified date.' );
	}

	/**
	 * @testdox Updates both tables with the same date when sync is enabled.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_authoritative Whether HPOS is authoritative.
	 */
	public function test_updates_both_tables_when_sync_is_enabled( bool $hpos_authoritative ): void {
		$this->toggle_cot_authoritative( $hpos_authoritative );
		$this->enable_cot_sync();
		$order_id = OrderHelper::create_order()->get_id();
		$this->set_old_modified_dates( $order_id );

		$this->sut->update_modified_date( $order_id );

		$orders_table_date = $this->get_orders_table_date_updated_gmt( $order_id );
		$this->assertNotSame( self::OLD_DATE_GMT, $orders_table_date, 'The orders table modified date should be updated.' );
		$this->assertSame( $orders_table_date, $this->get_post_modified_gmt( $order_id ), 'Both tables should have the same modified date.' );
	}

	/**
	 * Set the modified date of an order in both tables to a known old value, where the rows exist.
	 *
	 * @param int $order_id The order ID.
	 */
	private function set_old_modified_dates( int $order_id ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => self::OLD_DATE_GMT,
				'post_modified_gmt' => self::OLD_DATE_GMT,
			),
			array( 'ID' => $order_id )
		);
		$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'date_updated_gmt' => self::OLD_DATE_GMT ), array( 'id' => $order_id ) );
		clean_post_cache( $order_id );
		wc_get_container()->get( OrdersTableDataStore::class )->clear_cached_data( array( $order_id ) );
	}

	/**
	 * Get the modified date of an order post.
	 *
	 * @param int $order_id The order ID.
	 * @return string|null The GMT modified date.
	 */
	private function get_post_modified_gmt( int $order_id ): ?string {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT post_modified_gmt FROM {$wpdb->posts} WHERE ID = %d", $order_id ) );
	}

	/**
	 * Get the modified date of an order in the orders table.
	 *
	 * @param int $order_id The order ID.
	 * @return string|null The GMT modified date.
	 */
	private function get_orders_table_date_updated_gmt( int $order_id ): ?string {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is provided by the data store.
		return $wpdb->get_var( $wpdb->prepare( "SELECT date_updated_gmt FROM {$orders_table} WHERE id = %d", $order_id ) );
	}
}
