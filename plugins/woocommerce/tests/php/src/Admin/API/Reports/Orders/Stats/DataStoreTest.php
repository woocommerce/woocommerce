<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for Orders Stats DataStore.
 */
class DataStoreTest extends WC_Unit_Test_Case {

	/**
	 * Previous woocommerce_db_version for restore.
	 *
	 * @var mixed
	 */
	private $previous_db_version;

	/**
	 * Previous woocommerce_analytics_uses_old_full_refund_data for restore.
	 *
	 * @var mixed
	 */
	private $previous_old_full_refund_flag;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->previous_db_version           = get_option( 'woocommerce_db_version' );
		$this->previous_old_full_refund_flag = get_option( 'woocommerce_analytics_uses_old_full_refund_data' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( false !== $this->previous_db_version ) {
			update_option( 'woocommerce_db_version', $this->previous_db_version );
		} else {
			delete_option( 'woocommerce_db_version' );
		}
		if ( false !== $this->previous_old_full_refund_flag ) {
			update_option( 'woocommerce_analytics_uses_old_full_refund_data', $this->previous_old_full_refund_flag );
		} else {
			delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Lump-sum full refund without _refund_type stores parent product net in order stats.
	 */
	public function test_lump_sum_full_refund_without_refund_type_uses_parent_net_total(): void {
		update_option( 'woocommerce_db_version', '10.2.0' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );

		$order = WC_Helper_Order::create_order();
		// Add cart tax so we assert tax and shipping are both stripped from net, not only shipping.
		$order->set_cart_tax( 5.00 );
		$order->set_total( 55.00 );
		$order->save();
		$order->update_status( 'completed' );

		$remaining = (float) wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
		$refund    = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $remaining,
				'line_items' => array(),
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $refund );

		global $wpdb;
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->delete(
				$wpdb->prefix . 'wc_orders_meta',
				array(
					'order_id' => $refund->get_id(),
					'meta_key' => '_refund_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Test fixture: clear refund type meta.
				),
				array( '%d', '%s' )
			);
		} else {
			delete_post_meta( $refund->get_id(), '_refund_type' );
		}

		if ( OrderUtil::orders_cache_usage_is_enabled() ) {
			wc_get_container()->get( OrderCache::class )->remove( $refund->get_id() );
		}

		$refund_after_clear = wc_get_order( $refund->get_id() );
		$this->assertInstanceOf( \WC_Order_Refund::class, $refund_after_clear );
		$this->assertEmpty( $refund_after_clear->get_meta( '_refund_type', true ) );

		OrdersStatsDataStore::sync_order( $refund->get_id() );

		$net_total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT net_total FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
				$refund->get_id()
			)
		);

		$expected_net = -1 * ( $order->get_total() - $order->get_total_tax() - $order->get_shipping_total() );

		$this->assertEqualsWithDelta( $expected_net, $net_total, 0.02 );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * @testdox A partial refund followed by a full refund does not double-count the returns amount.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/66217: the full-refund
	 * row used to store the whole parent order total again, ignoring the amount an earlier partial
	 * refund had already recorded, so the Revenue report over-counted returns.
	 */
	public function test_partial_then_full_refund_does_not_double_count_returns(): void {
		update_option( 'woocommerce_db_version', '10.2.0' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );

		// Order: net 40 (4 x $10 product) + tax 5 + shipping 10 = 55 gross.
		$order = WC_Helper_Order::create_order();
		$order->set_cart_tax( 5.00 );
		$order->set_total( 55.00 );
		$order->save();
		$order->update_status( 'completed' );

		$product_item_id = array_key_first( $order->get_items() );

		// Partial refund: 2 of the 4 product units ($20) with a real line-item breakdown.
		$partial = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20.00,
				'line_items' => array(
					$product_item_id => array(
						'qty'          => 2,
						'refund_total' => 20.00,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $partial );

		// Full refund of the remainder as a lump sum (no line items), flagged as a full refund.
		$remaining = (float) wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
		$full      = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $remaining,
				'line_items' => array(),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $full );
		$full->update_meta_data( '_refund_type', 'full' );
		$full->save_meta_data();
		if ( OrderUtil::orders_cache_usage_is_enabled() ) {
			wc_get_container()->get( OrderCache::class )->remove( $full->get_id() );
		}

		OrdersStatsDataStore::sync_order( $order->get_id() );
		OrdersStatsDataStore::sync_order( $partial->get_id() );
		OrdersStatsDataStore::sync_order( $full->get_id() );

		global $wpdb;

		// Reported returns = absolute gross (net + tax + shipping) summed over the refund rows.
		$returns = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ABS( SUM( net_total + tax_total + shipping_total ) ) FROM {$wpdb->prefix}wc_order_stats WHERE parent_id = %d",
				$order->get_id()
			)
		);
		// Once (the order gross), not the partial refund counted twice ($75 before the fix).
		$this->assertEqualsWithDelta( 55.00, $returns, 0.02 );

		// The net portion across refunds should reconstruct the order net exactly once.
		$refunded_net = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( net_total ) FROM {$wpdb->prefix}wc_order_stats WHERE parent_id = %d",
				$order->get_id()
			)
		);
		$this->assertEqualsWithDelta( -40.00, $refunded_net, 0.02 );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * @testdox Deleting a refund removes its analytics rows while keeping the parent order's rows.
	 *
	 * Regression test for HPOS refund deletion leaving orphaned analytics rows:
	 * OrdersTableRefundDataStore::delete() fired no hooks, so the refund's
	 * wc_order_stats and wc_order_product_lookup rows survived the deletion and
	 * permanently skewed Revenue, Orders and Products reports.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/48955
	 */
	public function test_deleting_refund_removes_analytics_rows(): void {
		global $wpdb;

		$order = WC_Helper_Order::create_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();

		$items  = array_values( $order->get_items() );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order_id,
				'amount'     => 10,
				'line_items' => array(
					$items[0]->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $refund );
		$refund_id = $refund->get_id();

		// Import both records, as the woocommerce_update_order and
		// woocommerce_refund_created flows would.
		OrdersScheduler::import( $order_id );
		OrdersScheduler::import( $refund_id );

		$stats_rows  = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $id )
			);
		};
		$lookup_rows = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_id = %d", $id )
			);
		};

		$this->assertSame( 1, $stats_rows( $order_id ), 'Parent order should have a stats row after import.' );
		$this->assertSame( 1, $stats_rows( $refund_id ), 'Refund should have a stats row after import.' );
		$this->assertGreaterThan( 0, $lookup_rows( $refund_id ), 'Refund should have product lookup rows after import.' );

		// Delete the refund the way the admin UI and REST API do.
		$refund->delete( true );

		$this->assertSame( 0, $stats_rows( $refund_id ), 'Deleting a refund should remove its stats row.' );
		$this->assertSame( 0, $lookup_rows( $refund_id ), 'Deleting a refund should remove its product lookup rows.' );
		$this->assertSame( 1, $stats_rows( $order_id ), 'Deleting a refund should keep the parent order stats row.' );

		WC_Helper_Order::delete_order( $order_id );
	}

	/**
	 * @testdox delete_refund is a no-op when no stats row exists for the refund.
	 *
	 * On the CPT and HPOS-with-sync paths the delete_post hook has already
	 * cleaned up by the time woocommerce_delete_order_refund fires, so the
	 * handler must not fire the delete-stats cascade a second time.
	 */
	public function test_delete_refund_is_noop_when_no_stats_row_exists(): void {
		$fired    = 0;
		$callback = function () use ( &$fired ) {
			++$fired;
		};
		add_action( 'woocommerce_analytics_delete_order_stats', $callback );

		OrdersStatsDataStore::delete_refund( 987654321 );

		remove_action( 'woocommerce_analytics_delete_order_stats', $callback );

		$this->assertSame( 0, $fired, 'delete_refund should not fire the delete-stats cascade when no row exists.' );
	}
}
