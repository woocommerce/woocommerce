<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
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
		$this->previous_db_version          = get_option( 'woocommerce_db_version' );
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
		$order->update_status( 'completed' );

		$remaining = (float) wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
		$refund    = wc_create_refund(
			array(
				'order_id'     => $order->get_id(),
				'amount'       => $remaining,
				'line_items'   => array(),
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $refund );

		$refund->delete_meta_data( '_refund_type' );
		$refund->save();

		OrdersStatsDataStore::sync_order( $refund->get_id() );

		global $wpdb;
		$net_total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT net_total FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
				$refund->get_id()
			)
		);

		$expected_net = -1 * ( (float) $order->get_total() - (float) $order->get_total_tax() - (float) $order->get_shipping_total() );

		$this->assertEqualsWithDelta( $expected_net, (float) $net_total, 0.02 );

		WC_Helper_Order::delete_order( $order->get_id() );
	}
}
