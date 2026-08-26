<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order;
use WC_Product;

/**
 * Tests for the order fulfillment status regeneration tool of the Orders Stats DataStore.
 */
class DataStoreFulfillmentsTest extends OrdersStatsTestCase {

	/**
	 * Set the database version and clear the fulfillment-status column flag for this class.
	 */
	public static function setUpBeforeClass(): void {
		// Must come first: the parent reconnects `$wpdb`, which discards anything this
		// method has written but not committed.
		parent::setUpBeforeClass();

		$db_version = strstr( WC()->version, '-', true );
		$db_version = $db_version ? $db_version : WC()->version;
		update_option( 'woocommerce_db_version', $db_version );

		delete_option( OrdersStatsDataStore::OPTION_ORDER_STATS_TABLE_HAS_COLUMN_ORDER_FULFILLMENT_STATUS );
	}

	/**
	 * @testdox The regeneration tool updates fulfillment status only for orders with fulfillments.
	 */
	public function test_regenerate_order_fulfillment_status_updates_orders_with_fulfillments(): void {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		$prev_fulfillments_opt = get_option( 'woocommerce_feature_fulfillments_enabled', null );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );

		try {
			// Enable fulfillments feature.
			$controller = wc_get_container()->get( FulfillmentsController::class );
			$controller->register();
			$controller->initialize_fulfillments();

			// Reset migration state.
			delete_option( 'woocommerce_analytics_order_fulfillment_status_regenerated' );
			delete_transient( 'woocommerce_analytics_fulfillment_status_progress' );

			// Ensure column exists.
			OrdersStatsDataStore::add_fulfillment_status_column();

			$product = WC_Helper_Product::create_simple_product();

			$fulfilled_order           = WC_Helper_Order::create_order( get_current_user_id(), $product );
			$partially_fulfilled_order = WC_Helper_Order::create_order( get_current_user_id(), $product );
			$unfulfilled_order         = WC_Helper_Order::create_order( get_current_user_id(), $product );
			$no_fulfillment_order_1    = WC_Helper_Order::create_order( get_current_user_id(), $product );
			$no_fulfillment_order_2    = WC_Helper_Order::create_order( get_current_user_id(), $product );

			WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

			$this->add_fulfillment_to_order( $fulfilled_order, 'fulfilled', $product );
			$this->add_fulfillment_to_order( $partially_fulfilled_order, 'partially_fulfilled', $product );
			$this->add_fulfillment_to_order( $unfulfilled_order, 'unfulfilled', $product );

			Analytics::get_instance()->run_regenerate_order_fulfillment_status_tool();

			// Fetch all fulfillment statuses in a single query.
			$statuses = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT order_id, fulfillment_status
					FROM {$wpdb->prefix}wc_order_stats
					WHERE order_id IN (%d, %d, %d, %d, %d)
					ORDER BY order_id ASC",
					$fulfilled_order->get_id(),
					$partially_fulfilled_order->get_id(),
					$unfulfilled_order->get_id(),
					$no_fulfillment_order_1->get_id(),
					$no_fulfillment_order_2->get_id()
				),
				OBJECT_K
			);

			// Verify orders with fulfillments.
			$this->assertEquals( 'fulfilled', $statuses[ $fulfilled_order->get_id() ]->fulfillment_status, 'The fulfilled order should have fulfilled status' );
			$this->assertEquals( 'partially_fulfilled', $statuses[ $partially_fulfilled_order->get_id() ]->fulfillment_status, 'The partially fulfilled order should have partially_fulfilled status' );
			$this->assertEquals( 'unfulfilled', $statuses[ $unfulfilled_order->get_id() ]->fulfillment_status, 'The unfulfilled order should have unfulfilled status' );

			// Verify orders without fulfillments remain NULL.
			$this->assertNull( $statuses[ $no_fulfillment_order_1->get_id() ]->fulfillment_status, 'Orders without fulfillments should have a NULL fulfillment_status' );
			$this->assertNull( $statuses[ $no_fulfillment_order_2->get_id() ]->fulfillment_status, 'Orders without fulfillments should have a NULL fulfillment_status' );

			// Verify completion.
			$regenerated = get_option( 'woocommerce_analytics_order_fulfillment_status_regenerated' );
			$this->assertTrue( (bool) $regenerated, 'Migration should be marked as completed' );

			// Verify transient cleanup.
			$progress = get_transient( 'woocommerce_analytics_fulfillment_status_progress' );
			$this->assertFalse( $progress, 'Progress transient should be deleted after completion' );
		} finally {
			delete_option( 'woocommerce_analytics_order_fulfillment_status_regenerated' );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_fulfillment_meta" );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_fulfillments" );
			delete_transient( 'woocommerce_analytics_fulfillment_status_progress' );

			if ( null === $prev_fulfillments_opt ) {
				delete_option( 'woocommerce_feature_fulfillments_enabled' );
			} else {
				update_option( 'woocommerce_feature_fulfillments_enabled', $prev_fulfillments_opt );
			}
		}
	}

	/**
	 * Add a fulfillment record and set the order fulfillment status.
	 *
	 * This directly inserts into the fulfillments table and sets the order meta
	 * to simulate what the fulfillments system would do in production.
	 *
	 * @param WC_Order   $order Order object.
	 * @param string     $fulfillment_status Fulfillment status (fulfilled, partially_fulfilled, unfulfilled).
	 * @param WC_Product $product Product to add to the fulfillment.
	 *
	 * @return Fulfillment The created fulfillment object.
	 */
	private function add_fulfillment_to_order( $order, $fulfillment_status, $product ) {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );

		$fulfillment->set_items(
			array(
				array(
					'item_id' => $product->get_id(),
					'qty'     => 4,
				),
			)
		);
		$fulfillment->set_status( $fulfillment_status );
		$fulfillment->save();

		// Re-fetch the order to pick up meta changes made by hooks during save,
		// avoiding duplicate _fulfillment_status entries from separate instances.
		$order = wc_get_order( $order->get_id() );
		$order->update_meta_data( '_fulfillment_status', $fulfillment_status );
		$order->save();

		return $fulfillment;
	}
}
