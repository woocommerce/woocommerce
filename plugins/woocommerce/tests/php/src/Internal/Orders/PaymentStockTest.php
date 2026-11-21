<?php
/**
 * Payment stock tests.
 *
 * @package WooCommerce\Tests\Internal\Orders
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\PaymentStock;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for PaymentStock.
 */
class PaymentStockTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var PaymentStock
	 */
	private $sut;

	/** Set up stock management. */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_schema_version', 430 );
		update_option( 'woocommerce_hold_stock_minutes', 15 );
		$this->sut = new PaymentStock();
	}

	/** @testdox A pending order cannot start payment after its stock was sold. */
	public function test_expired_pending_order_with_sold_stock_is_rejected(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		wc_reserve_stock_for_order( $order );
		WC_Helper_Order::expire_stock_hold( $order );
		WC_Helper_Order::create_unpaid_order( $product )->payment_complete();

		$error = $this->sut->get_stock_error( $order );
		$this->assertNotNull( $error );
		$this->assertSame( 'woocommerce_rest_out_of_stock', $error['code'] );
		$this->assertStringContainsString( $product->get_name(), $error['message'] );
		$this->assertNull( wc_get_order( $order->get_id() )->get_date_paid() );
		$this->expectException( ReserveStockException::class );
		$this->sut->reserve_stock_for_payment( $order );
	}

	/** @testdox Reading an expired hold leaves it unchanged; payment and retries reserve stock for the configured duration. */
	public function test_payment_takes_fresh_reservation(): void {
		global $wpdb;
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		wc_reserve_stock_for_order( $order );
		WC_Helper_Order::expire_stock_hold( $order );

		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
		$this->sut->reserve_stock_for_payment( $order );
		$this->sut->reserve_stock_for_payment( $order );

		$this->assertSame( 1, wc_get_held_stock_quantity( $product ) );
		$minutes = $wpdb->get_var( $wpdb->prepare( "SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires) FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d", $order->get_id() ) );
		$this->assertGreaterThanOrEqual( 14, (int) $minutes );
		$this->assertLessThanOrEqual( 15, (int) $minutes );
		$this->assertNull( $order->get_date_paid() );
	}

	/** @testdox A different customer's valid reservation blocks payment. */
	public function test_competing_hold_is_not_available_stock(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$other   = WC_Helper_Order::create_unpaid_order( $product );
		wc_reserve_stock_for_order( $other );

		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
		$this->expectException( ReserveStockException::class );
		$this->sut->reserve_stock_for_payment( $order );
	}

	/** @testdox Starting payment renews a valid own hold even when a late payment reduced raw stock. */
	public function test_own_reservation_remains_allocated(): void {
		global $wpdb;
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		wc_reserve_stock_for_order( $order );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->wc_reserved_stock} SET expires = NOW() + INTERVAL 1 MINUTE WHERE order_id = %d", $order->get_id() ) );
		$expires = $wpdb->get_var( $wpdb->prepare( "SELECT expires FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d", $order->get_id() ) );
		wc_update_product_stock( $product, 0, 'set' );

		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->assertSame( $expires, $wpdb->get_var( $wpdb->prepare( "SELECT expires FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d", $order->get_id() ) ) );
		$this->sut->reserve_stock_for_payment( $order );
		$minutes = $wpdb->get_var( $wpdb->prepare( "SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires) FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d", $order->get_id() ) );
		$this->assertGreaterThanOrEqual( 14, (int) $minutes );
	}

	/** @testdox Stock already reduced for the same order is not required twice. */
	public function test_reduced_order_does_not_reserve_again(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$order->update_status( OrderStatus::ON_HOLD );

		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->sut->reserve_stock_for_payment( $order );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
		$this->assertEquals( 0, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/** @testdox Multiple lines sharing one stock record require their combined quantity. */
	public function test_combines_lines_for_same_stock_record(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$order->add_product( $product, 1 );
		$order->save();

		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
	}

	/** @testdox Disabling reservations does not disable the stock check. */
	public function test_disabled_holds_still_check_stock(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		update_option( 'woocommerce_hold_stock_minutes', '' );
		$this->sut->reserve_stock_for_payment( $order );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );

		wc_update_product_stock( $product, 0, 'set' );
		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
	}

	/** @testdox Disabling stock management preserves the existing payment behavior. */
	public function test_disabled_stock_management_is_unchanged(): void {
		$order = WC_Helper_Order::create_unpaid_order( WC_Helper_Product::create_stock_managed_product() );
		update_option( 'woocommerce_manage_stock', 'no' );
		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->sut->reserve_stock_for_payment( $order );
	}

	/** @testdox Explicit payment-stock filter overrides remain effective. */
	public function test_stock_filter_override_does_not_fail_reservation(): void {
		$product = WC_Helper_Product::create_stock_managed_product( 0 );
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		add_filter( 'woocommerce_pay_order_product_in_stock', '__return_true' );
		add_filter( 'woocommerce_pay_order_product_has_enough_stock', '__return_true' );

		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->sut->reserve_stock_for_payment( $order );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
	}

	/** @testdox Permitted backorders do not require a limited-stock reservation. */
	public function test_backorders_are_preserved(): void {
		$product = WC_Helper_Product::create_stock_managed_product( 0 );
		$product->set_backorders( 'yes' );
		$product->save();
		$order = WC_Helper_Order::create_unpaid_order( $product );

		$this->assertNull( $this->sut->get_stock_error( $order ) );
		$this->sut->reserve_stock_for_payment( $order );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
	}

	/** @testdox A payment stock filter can refuse a payment even when this order has a valid hold. */
	public function test_filter_can_reject_an_allocated_order(): void {
		$order = WC_Helper_Order::create_unpaid_order( WC_Helper_Product::create_stock_managed_product() );
		wc_reserve_stock_for_order( $order );
		add_filter( 'woocommerce_pay_order_product_in_stock', '__return_false' );
		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
	}

	/**
	 * @testdox A failed hold write stops payment and restores the previous holds without a statement retry.
	 * @testWith [1, false]
	 *           [2, false]
	 *           [2, true]
	 * @param int  $failed_write The reservation write that fails.
	 * @param bool $renew_existing_holds Whether the order already has valid holds to renew.
	 */
	public function test_reservation_write_failure( int $failed_write, bool $renew_existing_holds ): void {
		global $wpdb;
		$products = array( WC_Helper_Product::create_stock_managed_product(), WC_Helper_Product::create_stock_managed_product() );
		$order    = WC_Helper_Order::create_unpaid_order( $products[0] );
		$order->add_product( $products[1], 1 );
		$order->save();
		if ( $renew_existing_holds ) {
			wc_reserve_stock_for_order( $order );
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->wc_reserved_stock} SET expires = NOW() + INTERVAL 1 MINUTE WHERE order_id = %d", $order->get_id() ) );
		}
		$holds_before = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d ORDER BY product_id", $order->get_id() ), ARRAY_A );
		$table        = $wpdb->wc_reserved_stock;
		$attempts     = 0;
		add_filter(
			'query',
			static function ( $query ) use ( $table, &$attempts, $failed_write ) {
				$is_hold_write = false !== strpos( $query, 'INSERT INTO ' . $table ) || false !== strpos( $query, 'UPDATE ' . $table );
				if ( $is_hold_write && ++$attempts === $failed_write ) {
					return 'SELECT * FROM missing_payment_stock_test_table';
				}
				return $query;
			}
		);
		$previous = $wpdb->suppress_errors();
		try {
			$this->sut->reserve_stock_for_payment( $order );
			$this->fail( 'A failed reservation write must stop payment.' );
		} catch ( ReserveStockException $e ) {
			$this->assertSame( 'woocommerce_product_stock_unavailable', $e->getErrorCode() );
			$this->assertSame( 500, $e->getCode() );
		} finally {
			$wpdb->suppress_errors( $previous );
		}
		$this->assertSame( $failed_write, $attempts, 'Do not retry one statement after the transaction may have rolled back.' );
		$holds_after = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d ORDER BY product_id", $order->get_id() ), ARRAY_A );
		$this->assertSame( $holds_before, $holds_after, 'A failed payment attempt must restore the original hold quantities and expiry times.' );
		foreach ( $products as $product ) {
			$this->assertSame( $renew_existing_holds ? 1 : 0, wc_get_held_stock_quantity( $product ) );
		}
	}

	/** @testdox Item quantity filters affect the amount required before payment starts. */
	public function test_item_quantity_filter_is_applied(): void {
		$order = WC_Helper_Order::create_unpaid_order( WC_Helper_Product::create_stock_managed_product() );
		add_filter(
			'woocommerce_order_item_quantity',
			static function () {
				return 2;
			}
		);
		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
	}

	/** @testdox Retrying a failed payment creates a hold that blocks other orders. */
	public function test_failed_order_retry_reservation_is_counted(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$order->update_status( OrderStatus::FAILED );

		$this->sut->reserve_stock_for_payment( $order );

		$this->assertSame( OrderStatus::PENDING, wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( 1, wc_get_held_stock_quantity( $product ) );
		$this->assertNotNull( $this->sut->get_stock_error( WC_Helper_Order::create_unpaid_order( $product ) ) );
	}

	/** @testdox Variations that share parent stock require their combined quantity. */
	public function test_combines_variations_with_parent_stock(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->save();
		$ids   = $product->get_children();
		$order = WC_Helper_Order::create_unpaid_order( wc_get_product( $ids[0] ) );
		$order->add_product( wc_get_product( $ids[1] ), 1 );
		$order->save();

		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
	}

	/** @testdox Failed reservation reads produce a retry message instead of a stock shortage. */
	public function test_failed_reservation_read_is_a_technical_error(): void {
		global $wpdb;
		$order = WC_Helper_Order::create_unpaid_order( WC_Helper_Product::create_stock_managed_product() );
		$table = $wpdb->wc_reserved_stock;
		add_filter(
			'query',
			static function ( $query ) use ( $table ) {
				return false !== strpos( $query, 'SELECT product_id, stock_quantity FROM ' . $table ) ? 'SELECT * FROM missing_payment_stock_test_table' : $query;
			}
		);
		$previous = $wpdb->suppress_errors();
		try {
			$error = $this->sut->get_stock_error( $order );
			$this->assertSame( 'woocommerce_product_stock_unavailable', $error['code'] );
			$this->assertNotEmpty( $error['message'] );
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/** @testdox Payment reserves only the order lines whose stock has not been reduced. */
	public function test_mixed_reduced_and_unreduced_lines(): void {
		$reduced   = WC_Helper_Product::create_stock_managed_product();
		$available = WC_Helper_Product::create_stock_managed_product();
		$order     = WC_Helper_Order::create_unpaid_order( $reduced );
		$item      = current( $order->get_items() );
		$item->update_meta_data( '_reduced_stock', 1 );
		$item->save();
		wc_update_product_stock( $reduced, 0, 'set' );
		$order->add_product( $available, 1 );
		$order->save();

		$this->sut->reserve_stock_for_payment( $order );

		$this->assertSame( 0, wc_get_held_stock_quantity( $reduced ) );
		$this->assertSame( 1, wc_get_held_stock_quantity( $available ) );
	}

	/** @testdox Disabling quantity tracking does not make a sold-out product available for payment. */
	public function test_manual_out_of_stock_with_quantity_tracking_disabled(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$product->set_manage_stock( false );
		$product->set_stock_status( \Automattic\WooCommerce\Enums\ProductStockStatus::OUT_OF_STOCK );
		$product->save();
		$order = WC_Helper_Order::create_unpaid_order( $product );
		update_option( 'woocommerce_manage_stock', 'no' );

		$this->assertNotNull( $this->sut->get_stock_error( $order ) );
		$this->expectException( ReserveStockException::class );
		$this->sut->reserve_stock_for_payment( $order );
	}

	/** @testdox Payment cannot trust a hold that expired after the initial stock check. */
	public function test_expiring_own_hold_is_rechecked_before_payment(): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		wc_reserve_stock_for_order( $order );
		add_filter(
			'woocommerce_pay_order_product_in_stock',
			function ( $in_stock ) use ( $order, $product ) {
				WC_Helper_Order::expire_stock_hold( $order );
				wc_update_product_stock( $product, 0, 'set' );
				return $in_stock;
			}
		);

		$this->expectException( ReserveStockException::class );
		$this->sut->reserve_stock_for_payment( $order );
	}

	/**
	 * @testdox Payment checks and reserves the filtered quantity without applying the filter twice.
	 * @testWith [2]
	 *           [1.5]
	 * @param int|float $quantity Filtered stock quantity.
	 */
	public function test_filtered_reservation_quantity( $quantity ): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$product = WC_Helper_Product::create_stock_managed_product( 2 );
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$calls   = 0;
		add_filter(
			'woocommerce_order_item_quantity',
			static function () use ( &$calls, $quantity ) {
				++$calls;
				return $quantity;
			}
		);

		$this->sut->reserve_stock_for_payment( $order );

		$this->assertSame( 1, $calls );
		$this->assertEquals( $quantity, wc_get_held_stock_quantity( $product ) );
		$this->assertNotNull( $this->sut->get_stock_error( WC_Helper_Order::create_unpaid_order( $product ) ) );
	}

	/**
	 * @testdox A failed save leaves the original order status and no new stock hold.
	 * @testWith ["woocommerce_before_order_object_save"]
	 *           ["woocommerce_before_order_item_object_save"]
	 * @param string $hook The save step that fails.
	 */
	public function test_failed_save_rolls_back_hold_and_status( string $hook ): void {
		$product = WC_Helper_Product::create_stock_managed_product();
		$order   = WC_Helper_Order::create_unpaid_order( $product );
		$order->update_status( OrderStatus::FAILED );
		$item = current( $order->get_items() );
		$item->set_name( 'A changed order item' );
		add_action(
			$hook,
			static function () {
				throw new \Exception( 'Simulated save failure' );
			}
		);

		try {
			$this->sut->reserve_stock_for_payment( $order );
			$this->fail( 'A failed order save must stop payment.' );
		} catch ( ReserveStockException $e ) {
			$this->assertSame( 500, $e->getCode() );
		}
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
		$this->assertSame( OrderStatus::FAILED, wc_get_order( $order->get_id() )->get_status() );
	}

	/** @testdox A stock amount filter cannot turn a required quantity into a negative reservation. */
	public function test_invalid_normalized_quantity_is_rejected(): void {
		$order = WC_Helper_Order::create_unpaid_order( WC_Helper_Product::create_stock_managed_product() );
		add_filter(
			'woocommerce_stock_amount',
			static function () {
				return -1;
			}
		);

		try {
			$this->sut->reserve_stock_for_payment( $order );
			$this->fail( 'A negative quantity must stop payment.' );
		} catch ( ReserveStockException $e ) {
			$this->assertSame( 500, $e->getCode() );
		}
	}
}
