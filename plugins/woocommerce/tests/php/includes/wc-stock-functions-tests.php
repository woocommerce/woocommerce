<?php
/**
 * Unit tests for wc-stock-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\Enums\OrderInternalStatus;

/**
 * Class WC_Stock_Functions_Tests.
 */
class WC_Stock_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * Product reused by independent stock transitions within one test method.
	 *
	 * @var WC_Product|null
	 */
	private $stock_product;

	/**
	 * @var array List of statuses which reduces stock from inventory.
	 */
	public $order_stock_reduce_statuses = array(
		OrderInternalStatus::PROCESSING,
		OrderInternalStatus::COMPLETED,
		OrderInternalStatus::ON_HOLD,
	);

	/**
	 * @var array List of statuses which restores stock back into inventory.
	 */
	public $order_stock_restore_statuses = array(
		OrderInternalStatus::CANCELLED,
		OrderInternalStatus::PENDING,
		OrderInternalStatus::FAILED,
	);

	/**
	 * @var array List of statuses which have no impact on inventory.
	 */
	public $order_stock_no_effect_statuses = array(
		OrderInternalStatus::REFUNDED,
	);

	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
		$this->stock_product = null;
	}

	/**
	 * Helper function to simulate creating order from cart.
	 *
	 * @param string $status Status for the newly created order.
	 */
	private function create_order_from_cart_with_status( $status ) {
		if ( ! $this->stock_product ) {
			$this->stock_product = WC_Helper_Product::create_simple_product(
				true,
				array(
					'manage_stock'   => true,
					'stock_quantity' => 10,
				)
			);
		} else {
			wc_update_product_stock( $this->stock_product, 10, 'set' );
		}
		$product = $this->stock_product;
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		$checkout = WC_Checkout::instance();
		$order    = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$order->set_status( $status );
		$order->save();
		return $order;
	}

	/**
	 * Run a callback with stock reservation enabled and the configured hold duration.
	 *
	 * @param string   $hold_stock_minutes Configured hold stock duration.
	 * @param callable $callback Callback to run.
	 * @return mixed
	 */
	private function with_stock_reservation_options( $hold_stock_minutes, $callback ) {
		$option_names         = array(
			'woocommerce_hold_stock_minutes',
			'woocommerce_manage_stock',
			'woocommerce_schema_version',
		);
		$missing_option_value = new stdClass();
		$original_options     = array();

		foreach ( $option_names as $option_name ) {
			$option_value                     = get_option( $option_name, $missing_option_value );
			$original_options[ $option_name ] = array(
				'exists' => $missing_option_value !== $option_value,
				'value'  => $option_value,
			);
		}

		update_option( 'woocommerce_hold_stock_minutes', $hold_stock_minutes );
		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_schema_version', 430 );

		try {
			return $callback();
		} finally {
			foreach ( $original_options as $option_name => $option_data ) {
				if ( $option_data['exists'] ) {
					update_option( $option_name, $option_data['value'] );
				} else {
					delete_option( $option_name );
				}
			}
		}
	}

	/**
	 * Capture stock reservation minutes observed by the stock hold filter.
	 *
	 * @param callable $reserve_stock_callback Callback that attempts to reserve stock.
	 * @return int|null
	 */
	private function get_captured_order_hold_stock_minutes( $reserve_stock_callback ) {
		$captured_minutes = null;
		$capture_minutes  = function ( $minutes ) use ( &$captured_minutes ) {
			$captured_minutes = $minutes;
			return 0;
		};

		add_filter( 'woocommerce_order_hold_stock_minutes', $capture_minutes );

		try {
			$reserve_stock_callback();
		} finally {
			remove_filter( 'woocommerce_order_hold_stock_minutes', $capture_minutes );
		}

		return $captured_minutes;
	}

	/**
	 * Helper function to change order status and assert product inventory values.
	 *
	 * @param string $status_from       Initial status of the order.
	 * @param string $status_to         Status to transition the order to.
	 * @param int    $before_transition Inventory value before order status is changed.
	 * @param int    $after_transition  Inventory value after order status change.
	 */
	private function transition_order_status_and_assert_stock_quantity( $status_from, $status_to, $before_transition, $after_transition ) {
		$order      = $this->create_order_from_cart_with_status( $status_from );
		$order_item = array_values( $order->get_items( 'line_item' ) )[0];

		$product = new WC_Product( $order_item->get_product_id() );
		$this->assertEquals( $before_transition, $product->get_stock_quantity() );

		// Changing status from UI also calls this method in metadata save hook. This has impact on stock levels, so simulate it here as well.
		wc_save_order_items( $order, $order->get_items() );

		$order->set_status( $status_to );
		$order->save();
		$product = new WC_Product( $order_item->get_product_id() );
		$this->assertEquals( $after_transition, $product->get_stock_quantity(), "Stock levels unexpected when transitioning from $status_from to $status_to." );
	}

	/**
	 * Test inventory count after order status transitions which reduces stock to another status which also reduces stock.
	 * Stock should have reduced once already, and should not reduce again.
	 */
	public function test_status_transition_stock_reduce_to_stock_reduce() {
		foreach ( $this->order_stock_reduce_statuses as $order_status_from ) {
			foreach ( $this->order_stock_reduce_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 9, 9 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which reduces stock to another status which restores stock.
	 * Should should have already reduced once, and will increase again after transitioning.
	 */
	public function test_status_transition_stock_reduce_to_stock_restore() {
		foreach ( $this->order_stock_reduce_statuses as $order_status_from ) {
			foreach ( $this->order_stock_restore_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 9, 10 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which reduces stock to another status which don't affect inventory.
	 * Stock should have already reduced, and will not change on transitioning.
	 */
	public function test_status_transition_stock_reduce_to_stock_no_effect() {
		foreach ( $this->order_stock_reduce_statuses as $order_status_from ) {
			foreach ( $this->order_stock_no_effect_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 9, 9 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which restores stock to another status which reduces stock.
	 * Stock should not have reduced, but will reduce after transition.
	 */
	public function test_status_transition_stock_restore_to_stock_reduce() {
		foreach ( $this->order_stock_restore_statuses as $order_status_from ) {
			foreach ( $this->order_stock_reduce_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 9 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which restores stock to another status which also restores stock.
	 * Stock should not have reduced, and will remain the same even after transition (i.e. should not be restocked again).
	 */
	public function test_status_transition_stock_restore_to_stock_restore() {
		foreach ( $this->order_stock_restore_statuses as $order_status_from ) {
			foreach ( $this->order_stock_restore_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 10 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which restores stock to another status which don't affect inventory.
	 * Stock should not have reduced, and will remain the same even after transition.
	 */
	public function test_status_transition_stock_restore_to_stock_no_effect() {
		foreach ( $this->order_stock_restore_statuses as $order_status_from ) {
			foreach ( $this->order_stock_no_effect_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 10 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which don't affect inventory stock to another status which reduces stock.
	 * Stock would not have been affected, but will reduce after transition.
	 */
	public function test_status_transition_stock_no_effect_to_stock_reduce() {
		foreach ( $this->order_stock_no_effect_statuses as $order_status_from ) {
			foreach ( $this->order_stock_reduce_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 9 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which don't affect inventory stock to another status which restores stock.
	 * Stock would not have been affected, and will not be restored after transition (since it was not reduced to begin with).
	 */
	public function test_status_transition_stock_no_effect_to_stock_restore() {
		foreach ( $this->order_stock_no_effect_statuses as $order_status_from ) {
			foreach ( $this->order_stock_restore_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 10 );
			}
		}
	}

	/**
	 * Test inventory count after order status transitions which don't affect inventory stock to another status which also don't affect inventory.
	 * Stock levels will not change before or after the transition.
	 */
	public function test_status_transition_stock_no_effect_to_stock_no_effect() {
		foreach ( $this->order_stock_no_effect_statuses as $order_status_from ) {
			foreach ( $this->order_stock_no_effect_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 10 );
			}
		}
	}

	/**
	 * An order that reduced stock while on-hold (e.g. an accepted-but-pending payment) and then fails
	 * should have its stock restored and its stock-reduced flag cleared, mirroring cancel/pending.
	 */
	public function test_on_hold_to_failed_restores_stock_and_clears_reduced_flag() {
		$order      = $this->create_order_from_cart_with_status( OrderInternalStatus::ON_HOLD );
		$product_id = array_values( $order->get_items( 'line_item' ) )[0]->get_product_id();

		$this->assertEquals( 9, wc_get_product( $product_id )->get_stock_quantity(), 'On-hold should reduce stock by the ordered quantity.' );
		$this->assertTrue( (bool) $order->get_data_store()->get_stock_reduced( $order->get_id() ), 'The stock-reduced flag should be set while on-hold.' );

		$order->set_status( OrderInternalStatus::FAILED );
		$order->save();

		$this->assertEquals( 10, wc_get_product( $product_id )->get_stock_quantity(), 'Failing an order that reduced stock should restore it.' );
		$this->assertFalse( (bool) $order->get_data_store()->get_stock_reduced( $order->get_id() ), 'The stock-reduced flag should be cleared after the stock is restored.' );

		$restore_notes = array_filter(
			wc_get_order_notes( array( 'order_id' => $order->get_id() ) ),
			function ( $note ) {
				return false !== strpos( $note->content, 'Stock levels increased' );
			}
		);
		$this->assertNotEmpty( $restore_notes, 'A "Stock levels increased" order note should be recorded when the stock is restored.' );
	}

	/**
	 * A plain pending -> failed order (a payment declined before any stock was reduced) should be a
	 * no-op: there is nothing to restore, so stock stays put.
	 */
	public function test_pending_to_failed_does_not_change_stock() {
		$order      = $this->create_order_from_cart_with_status( OrderInternalStatus::PENDING );
		$product_id = array_values( $order->get_items( 'line_item' ) )[0]->get_product_id();

		$this->assertEquals( 10, wc_get_product( $product_id )->get_stock_quantity(), 'Pending should not reduce stock.' );

		$order->set_status( OrderInternalStatus::FAILED );
		$order->save();

		$this->assertEquals( 10, wc_get_product( $product_id )->get_stock_quantity(), 'Failing an order that never reduced stock should not change stock.' );
	}

	/**
	 * Admin-path safety check. A failed order is a dead end in the gateway flow, but an admin can
	 * manually move it back to a paid status. Because the failure already restored the stock, doing
	 * so must reduce stock exactly once, not twice.
	 */
	public function test_reactivating_a_failed_order_reduces_stock_only_once() {
		$order      = $this->create_order_from_cart_with_status( OrderInternalStatus::ON_HOLD );
		$product_id = array_values( $order->get_items( 'line_item' ) )[0]->get_product_id();

		$order->set_status( OrderInternalStatus::FAILED );
		$order->save();
		$this->assertEquals( 10, wc_get_product( $product_id )->get_stock_quantity(), 'Failure should restore the reduced stock.' );

		// Admin manually re-activates the order.
		$order->set_status( OrderInternalStatus::PROCESSING );
		$order->save();
		$this->assertEquals( 9, wc_get_product( $product_id )->get_stock_quantity(), 'Re-activating the order should reduce stock exactly once.' );
		$this->assertTrue( (bool) $order->get_data_store()->get_stock_reduced( $order->get_id() ), 'The stock-reduced flag should be set again after re-activation.' );
	}

	/**
	 * Assert that a value is equal to another one and is of integer type.
	 *
	 * @param mixed $expected The value $actual must be equal to.
	 * @param mixed $actual The value to check for equality to $expected and for type.
	 */
	private function assertIsIntAndEquals( $expected, $actual ) {
		$this->assertEquals( $expected, $actual );
		self::assertIsInteger( $actual );
	}

	/**
	 * @testdox reserve_stock_for_order defaults to 60 minutes when no duration is provided.
	 */
	public function test_reserve_stock_for_order_defaults_to_60_minutes() {
		$minutes = $this->with_stock_reservation_options(
			'15',
			function () {
				$order         = wc_create_order();
				$reserve_stock = new ReserveStock();

				return $this->get_captured_order_hold_stock_minutes(
					function () use ( $reserve_stock, $order ) {
						$reserve_stock->reserve_stock_for_order( $order );
					}
				);
			}
		);

		$this->assertSame(
			60,
			$minutes,
			'Direct stock reservation calls should use the explicit default duration.'
		);
	}

	/**
	 * @testdox wc_reserve_stock_for_order passes the configured checkout stock hold duration.
	 */
	public function test_wc_reserve_stock_for_order_passes_configured_checkout_hold_duration() {
		$minutes = $this->with_stock_reservation_options(
			'15',
			function () {
				$order = wc_create_order();

				return $this->get_captured_order_hold_stock_minutes(
					function () use ( $order ) {
						wc_reserve_stock_for_order( $order );
					}
				);
			}
		);

		$this->assertSame(
			15,
			$minutes,
			'Core checkout stock reservation should use the configured hold duration.'
		);
	}

	/**
	 * Test wc_get_low_stock_amount with a simple product which has low stock amount set.
	 */
	public function test_wc_get_low_stock_amount_simple_set() {
		$product_low_stock_amount   = 5;
		$site_wide_low_stock_amount = 3;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Simple product, set low stock amount.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'     => true,
				'stock_quantity'   => 10,
				'low_stock_amount' => $product_low_stock_amount,
			)
		);

		$this->assertIsIntAndEquals( $product_low_stock_amount, wc_get_low_stock_amount( $product ) );
	}

	/**
	 * Test wc_get_low_stock_amount with a simple product which doesn't have low stock amount set.
	 */
	public function test_wc_get_low_stock_amount_simple_unset() {
		$site_wide_low_stock_amount = 3;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Simple product, don't set low stock amount.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		$this->assertIsIntAndEquals( $site_wide_low_stock_amount, wc_get_low_stock_amount( $product ) );
	}

	/**
	 * Test wc_get_low_stock_amount with a variable product which has low stock amount set on the variation level,
	 * but not on the parent level. Should use the value from the variation.
	 */
	public function test_wc_get_low_stock_amount_variation_set_parent_unset() {
		$site_wide_low_stock_amount = 3;
		$variation_low_stock_amount = 7;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Parent low stock amount NOT set.
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_manage_stock( false );
		$variable_product->save();

		// Set the variation low stock amount.
		$variations = $variable_product->get_available_variations( 'objects' );
		$var1       = $variations[0];
		$var1->set_manage_stock( true );
		$var1->set_low_stock_amount( $variation_low_stock_amount );
		$var1->save();

		$this->assertIsIntAndEquals( $variation_low_stock_amount, wc_get_low_stock_amount( $var1 ) );

		// Even after turning on manage stock on the parent, but with no value.
		$variable_product->set_manage_stock( true );
		$variable_product->save();
		$this->assertIsIntAndEquals( $variation_low_stock_amount, wc_get_low_stock_amount( $var1 ) );

		// Ans also after turning the manage stock off again on the parent.
		$variable_product->set_manage_stock( false );
		$variable_product->save();
		$this->assertIsIntAndEquals( $variation_low_stock_amount, wc_get_low_stock_amount( $var1 ) );
	}

	/**
	 * Test wc_get_low_stock_amount with a variable product which has low stock amount set on the variation level,
	 * and also on the parent level. Should use the value from the variation.
	 */
	public function test_wc_get_low_stock_amount_variation_set_parent_set() {
		$site_wide_low_stock_amount = 3;
		$parent_low_stock_amount    = 5;
		$variation_low_stock_amount = 7;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Set the parent low stock amount.
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_manage_stock( true );
		$variable_product->set_low_stock_amount( $parent_low_stock_amount );
		$variable_product->save();

		// Set the variation low stock amount.
		$variations = $variable_product->get_available_variations( 'objects' );
		$var1       = $variations[0];
		$var1->set_manage_stock( true );
		$var1->set_low_stock_amount( $variation_low_stock_amount );
		$var1->save();

		$this->assertIsIntAndEquals( $variation_low_stock_amount, wc_get_low_stock_amount( $var1 ) );
	}

	/**
	 * Test wc_get_low_stock_amount with a variable product which has low stock amount set on the parent level,
	 * but NOT on the variation level. Should use the value from the parent.
	 */
	public function test_wc_get_low_stock_amount_variation_unset_parent_set() {
		$site_wide_low_stock_amount = 3;
		$parent_low_stock_amount    = 5;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Set the parent low stock amount.
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_manage_stock( true );
		$variable_product->set_low_stock_amount( $parent_low_stock_amount );
		$variable_product->save();

		// Don't set the variation low stock amount.
		$variations = $variable_product->get_available_variations( 'objects' );
		$var1       = $variations[0];

		$this->assertIsIntAndEquals( $parent_low_stock_amount, wc_get_low_stock_amount( $var1 ) );
	}

	/**
	 * Test wc_get_low_stock_amount with a variable product which *doesn't have* low stock amount set either on the parent level,
	 * or on the variation level. Should use the value from the site-wide setting.
	 */
	public function test_wc_get_low_stock_amount_variation_unset_parent_unset() {
		$site_wide_low_stock_amount = 3;

		// Set the store-wide default.
		update_option( 'woocommerce_notify_low_stock_amount', strval( $site_wide_low_stock_amount ) );

		// Set the parent low stock amount.
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_manage_stock( false );

		// Don't set the variation low stock amount.
		$variations = $variable_product->get_available_variations( 'objects' );
		$var1       = $variations[0];
		$var1->set_manage_stock( false );

		$this->assertIsIntAndEquals( $site_wide_low_stock_amount, wc_get_low_stock_amount( $var1 ) );
	}
}
