<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Caches\OrderCache;
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
}
