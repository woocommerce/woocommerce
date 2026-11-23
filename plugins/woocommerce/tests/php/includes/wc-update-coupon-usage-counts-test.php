<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for wc_update_coupon_usage_counts() when another request changes the same order while it runs.
 *
 * Kept apart from WC_Order_Functions_Test because each test switches the order storage.
 */
class WC_Update_Coupon_Usage_Counts_Test extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Whether HPOS was enabled before the test.
	 *
	 * @var bool
	 */
	private $prev_hpos_enabled;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->prev_hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->toggle_cot_feature_and_usage( true );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		try {
			$this->clean_up_cot_setup();
			$this->toggle_cot_feature_and_usage( $this->prev_hpos_enabled );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Cancelling an order gives its coupon use back only once when another request already gave it back.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether HPOS is the authoritative order storage.
	 */
	public function test_gives_coupon_use_back_once_when_another_request_already_did( bool $hpos_enabled ): void {
		$this->toggle_cot_authoritative( $hpos_enabled );
		$order = $this->create_pending_order_with_counted_coupon( 'give-back-once' );

		$count_after_other_request = null;
		$this->run_once_between_flag_read_and_write(
			function () use ( $order, &$count_after_other_request ) {
				wc_update_coupon_usage_counts( $order->get_id() );
				$count_after_other_request = ( new WC_Coupon( 'give-back-once' ) )->get_usage_count();
			}
		);
		$order->update_status( OrderStatus::CANCELLED );

		$coupon = new WC_Coupon( 'give-back-once' );
		$this->assertSame( 1, $count_after_other_request, 'The other request should give the use back' );
		$this->assertSame( 1, $coupon->get_usage_count(), 'The use should be given back only once' );
		$this->assertCount( 1, $coupon->get_used_by(), 'Only one customer use record should be removed' );
	}

	/**
	 * @testdox Cancelling an order keeps its coupon use counted when another request marks the order as paid first.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether HPOS is the authoritative order storage.
	 */
	public function test_keeps_coupon_use_counted_when_order_is_paid_meanwhile( bool $hpos_enabled ): void {
		$this->toggle_cot_authoritative( $hpos_enabled );
		$order = $this->create_pending_order_with_counted_coupon( 'paid-meanwhile' );

		$this->run_once_between_flag_read_and_write(
			function () use ( $order ) {
				$this->store_order_status( $order->get_id(), OrderInternalStatus::PROCESSING );
			}
		);
		$order->update_status( OrderStatus::CANCELLED );

		$coupon = new WC_Coupon( 'paid-meanwhile' );
		$this->assertSame( 2, $coupon->get_usage_count(), 'A paid order should keep its coupon use' );
		$this->assertCount( 2, $coupon->get_used_by(), 'A paid order should keep its customer use record' );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order->get_id() ), 'A paid order should stay marked as counted' );
	}

	/**
	 * @testdox A custom status added to the invalid statuses gives the coupon use back, also when it is not a registered post status.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether HPOS is the authoritative order storage.
	 */
	public function test_gives_coupon_use_back_for_custom_invalid_status( bool $hpos_enabled ): void {
		$this->toggle_cot_authoritative( $hpos_enabled );
		add_filter(
			'wc_order_statuses',
			function ( $statuses ) {
				$statuses['wc-expired'] = 'Expired';
				return $statuses;
			}
		);
		add_filter(
			'woocommerce_update_coupon_usage_invalid_statuses',
			function ( $statuses ) {
				$statuses[] = 'expired';
				return $statuses;
			}
		);
		add_action( 'woocommerce_order_status_expired', 'wc_update_coupon_usage_counts' );
		$order = $this->create_pending_order_with_counted_coupon( 'custom-status' );

		$order->update_status( 'expired' );

		$this->assertSame( 1, ( new WC_Coupon( 'custom-status' ) )->get_usage_count(), 'The use should be given back' );
	}

	/**
	 * @testdox Cancelling an order gives its coupon use back when the order data store cannot clear the counted flag conditionally.
	 */
	public function test_gives_coupon_use_back_with_data_store_without_conditional_clear(): void {
		$this->toggle_cot_authoritative( false );
		$order    = $this->create_pending_order_with_counted_coupon( 'basic-data-store' );
		$order_id = $order->get_id();
		$this->store_order_status( $order_id, OrderInternalStatus::CANCELLED );
		clean_post_cache( $order_id );

		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) {
				$stores['test-order-without-conditional-clear'] = new class() extends Abstract_WC_Order_Data_Store_CPT {
					/**
					 * Get whether coupon usage was counted for the order.
					 *
					 * @param WC_Order|int $order Order or order ID.
					 * @return bool
					 */
					public function get_recorded_coupon_usage_counts( $order ) {
						return wc_string_to_bool( get_post_meta( WC_Order_Factory::get_order_id( $order ), '_recorded_coupon_usage_counts', true ) );
					}

					/**
					 * Set whether coupon usage was counted for the order.
					 *
					 * @param WC_Order|int $order Order or order ID.
					 * @param bool         $set   Whether the usage was counted.
					 */
					public function set_recorded_coupon_usage_counts( $order, $set ) {
						update_post_meta( WC_Order_Factory::get_order_id( $order ), '_recorded_coupon_usage_counts', wc_bool_to_string( $set ) );
					}
				};
				return $stores;
			}
		);
		$order_class = get_class(
			new class() extends WC_Order {
				/**
				 * Get a data store without clear_recorded_coupon_usage_counts().
				 *
				 * @return WC_Data_Store
				 */
				public function get_data_store() {
					return WC_Data_Store::load( 'test-order-without-conditional-clear' );
				}
			}
		);
		add_filter(
			'woocommerce_order_class',
			function () use ( $order_class ) {
				return $order_class;
			}
		);

		$this->assertFalse( wc_get_order( $order_id )->get_data_store()->has_callable( 'clear_recorded_coupon_usage_counts' ), 'The order should use the data store without the conditional clear' );

		wc_update_coupon_usage_counts( $order_id );

		$this->assertSame( 1, ( new WC_Coupon( 'basic-data-store' ) )->get_usage_count(), 'The use should be given back' );
		$this->assertSame( 'no', get_post_meta( $order_id, '_recorded_coupon_usage_counts', true ), 'The order should no longer be marked as counted' );
	}

	/**
	 * Create a pending order whose coupon use is counted, after another order already used the same coupon.
	 *
	 * @param string $code Coupon code.
	 * @return WC_Order The order; the coupon's usage count is 2.
	 */
	private function create_pending_order_with_counted_coupon( string $code ): WC_Order {
		WC_Helper_Coupon::create_coupon( $code );
		WC_Helper_Order::create_order()->apply_coupon( $code );

		$order = WC_Helper_Order::create_order();
		$order->apply_coupon( $code );

		$this->assertSame( 2, ( new WC_Coupon( $code ) )->get_usage_count(), 'Both orders should count their coupon use' );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order->get_id() ), 'The order should be marked as counted' );

		return $order;
	}

	/**
	 * Run a callback once, after wc_update_coupon_usage_counts() reads the order's counted flag and before it changes it.
	 *
	 * The invalid statuses filter runs in that gap, so the callback acts like another request that lands there.
	 *
	 * @param callable $callback Callback to run.
	 */
	private function run_once_between_flag_read_and_write( callable $callback ): void {
		$has_run = false;
		add_filter(
			'woocommerce_update_coupon_usage_invalid_statuses',
			function ( $statuses ) use ( $callback, &$has_run ) {
				if ( ! $has_run ) {
					$has_run = true;
					$callback();
				}
				return $statuses;
			}
		);
	}

	/**
	 * Write an order status straight to the authoritative storage, leaving order objects already loaded unchanged.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   Stored status, e.g. 'wc-processing'.
	 */
	private function store_order_status( int $order_id, string $status ): void {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'status' => $status ), array( 'id' => $order_id ) );
		} else {
			$wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'ID' => $order_id ) );
		}
	}
}
