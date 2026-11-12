<?php
/**
 * Unit tests for wc-stock-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;

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
		self::assertIsInt( $actual );
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
	 * @testdox Available stock stays constant while a paid order's hold is swapped for a stock reduction.
	 */
	public function test_stock_hold_counts_until_stock_is_reduced_on_payment() {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		$this->assert_available_stock_constant_through_payment( $product, $product, 1, 9 );
	}

	/**
	 * @testdox Available parent stock stays constant while a parent-managed variation order is paid.
	 */
	public function test_parent_managed_variation_hold_counts_until_stock_is_reduced_on_payment() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_manage_stock( true );
		$parent->set_stock_quantity( 10 );
		$parent->save();

		// The cart needs every attribute chosen, so use a variation that sets all of them.
		$variation = null;
		foreach ( $parent->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ! in_array( '', $child->get_variation_attributes(), true ) ) {
				$variation = $child;
				break;
			}
		}
		$this->assertNotNull( $variation, 'The helper should create a fully specified variation.' );
		$variation->set_manage_stock( false );
		$variation->save();

		$this->assertSame( $parent->get_id(), $variation->get_stock_managed_by_id(), 'The parent should manage the variation stock.' );

		$this->assert_available_stock_constant_through_payment( $variation, $parent, 2, 8 );
	}

	/**
	 * @testdox Available parent stock stays constant after each item when two variations share one hold.
	 */
	public function test_shared_hold_is_lowered_item_by_item_on_payment() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_manage_stock( true );
		$parent->set_stock_quantity( 10 );
		$parent->save();

		// The cart needs every attribute chosen, so use variations that set all of them.
		$variations = array();
		foreach ( $parent->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( count( $variations ) < 2 && ! in_array( '', $child->get_variation_attributes(), true ) ) {
				$child->set_manage_stock( false );
				$child->save();
				$variations[] = $child;
			}
		}
		$this->assertCount( 2, $variations, 'The helper should create two fully specified variations.' );

		// Two items of two units each share the parent's hold of four.
		$this->assert_available_stock_constant_through_payment( $variations, $parent, 2, 6 );
	}

	/**
	 * Pay for an order of $quantity units of each product and check available stock never moves.
	 *
	 * @param WC_Product|WC_Product[] $products           Product or products added to the cart.
	 * @param WC_Product              $stock_product      Product whose stock is managed.
	 * @param int                     $quantity           Units ordered of each product.
	 * @param int                     $expected_available Stock minus the order's units.
	 */
	private function assert_available_stock_constant_through_payment( $products, $stock_product, $quantity, $expected_available ) {
		$products = is_array( $products ) ? $products : array( $products );
		$observed = $this->with_stock_reservation_options(
			'60',
			function () use ( $products, $stock_product, $quantity, $expected_available ) {
				WC()->cart->empty_cart();
				foreach ( $products as $product ) {
					if ( $product->is_type( 'variation' ) ) {
						$cart_item_key = WC()->cart->add_to_cart( $product->get_parent_id(), $quantity, $product->get_id(), $product->get_variation_attributes() );
					} else {
						$cart_item_key = WC()->cart->add_to_cart( $product->get_id(), $quantity );
					}
					$this->assertNotFalse( $cart_item_key, 'The product should be added to the cart: ' . wc_print_notices( true ) );
				}
				WC()->cart->calculate_totals();

				$checkout = WC_Checkout::instance();
				$order    = new WC_Order();
				$checkout->set_data_from_cart( $order );
				$order->set_status( OrderInternalStatus::PENDING );
				$order->save();
				wc_reserve_stock_for_order( $order );

				$this->assertSame( $expected_available, $this->get_available_stock( $stock_product->get_id() ), 'The pending order should hold its units.' );

				$observed = array();
				foreach ( array( 9, 10, 11 ) as $priority ) {
					add_action(
						'woocommerce_order_status_processing',
						function () use ( &$observed, $stock_product, $priority ) {
							$observed[ $priority ] = $this->get_available_stock( $stock_product->get_id() );
						},
						$priority
					);
				}

				// Fires inside the reduction loop, once per item, after its stock is reduced and its share of the hold lowered.
				add_action(
					'woocommerce_reduce_order_item_stock',
					function () use ( &$observed, $stock_product ) {
						$observed['item'][] = $this->get_available_stock( $stock_product->get_id() );
					}
				);

				$order->update_status( OrderInternalStatus::PROCESSING );

				return $observed;
			}
		);

		$this->assertSame(
			array(
				9      => $expected_available,
				'item' => array_fill( 0, count( $products ), $expected_available ),
				10     => $expected_available,
				11     => $expected_available,
			),
			$observed,
			'Available stock should neither jump after the status is written nor drop while the hold and the reduction overlap.'
		);
		$this->assertSame( $expected_available, $this->get_available_stock( $stock_product->get_id() ), 'The reduced stock should be the only thing counted after the transition.' );
	}

	/**
	 * @testdox Stock holds count for orders in status $status: $expected.
	 *
	 * @dataProvider stock_hold_status_provider
	 *
	 * @param string $status   Internal order status.
	 * @param bool   $expected Whether the hold should count.
	 */
	public function test_stock_hold_counts_by_order_status( $status, $expected ) {
		$this->assertSame( $expected ? 2 : 0, $this->get_held_quantity_for_order_in_status( $status ), "A hold for a $status order should " . ( $expected ? '' : 'not ' ) . 'count.' );
	}

	/**
	 * Held stock as seen by wc_get_held_stock_quantity() for a two-unit hold on an order in $status.
	 *
	 * @param string $status Internal order status, registered on the fly if unknown.
	 * @return int
	 */
	private function get_held_quantity_for_order_in_status( $status ) {
		global $wpdb;

		return (int) $this->with_stock_reservation_options(
			'60',
			function () use ( $status, $wpdb ) {
				// Register the status the way an extension would, so it is stored with its prefix.
				$is_custom_status = null === get_post_status_object( $status );
				if ( $is_custom_status ) {
					register_post_status( $status, array( 'label' => $status ) );
					add_filter(
						'wc_order_statuses',
						function ( $statuses ) use ( $status ) {
							return $statuses + array( $status => $status );
						}
					);
				}

				try {
					$order   = $this->create_order_from_cart_with_status( $status );
					$product = $this->stock_product;

					$wpdb->delete( $wpdb->wc_reserved_stock, array( 'order_id' => $order->get_id() ) );
					// Expiry is compared against the database's NOW(), so write it there too.
					$wpdb->query(
						$wpdb->prepare(
							"INSERT INTO {$wpdb->wc_reserved_stock} ( `order_id`, `product_id`, `stock_quantity`, `timestamp`, `expires` ) VALUES ( %d, %d, 2, NOW(), NOW() + INTERVAL 1 HOUR )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$order->get_id(),
							$product->get_id()
						)
					);

					return wc_get_held_stock_quantity( $product );
				} finally {
					if ( $is_custom_status ) {
						_unregister_post_status( $status );
					}
				}
			}
		);
	}

	/**
	 * @testdox Failing an order releases its stock hold.
	 */
	public function test_failing_an_order_releases_its_hold() {
		$held = $this->with_stock_reservation_options(
			'60',
			function () {
				$order = $this->create_order_from_cart_with_status( OrderInternalStatus::PENDING );
				wc_reserve_stock_for_order( $order );
				$this->assertSame( 1, (int) wc_get_held_stock_quantity( $this->stock_product ), 'The pending order should hold its units.' );

				$order->update_status( OrderStatus::FAILED );

				return (int) wc_get_held_stock_quantity( $this->stock_product );
			}
		);

		$this->assertSame( 0, $held );
	}

	/**
	 * @testdox Reducing an order's stock releases its hold even when woocommerce_hold_stock_for_checkout is false.
	 */
	public function test_reducing_stock_releases_hold_regardless_of_hold_stock_filter() {
		$held = $this->with_stock_reservation_options(
			'60',
			function () {
				$order = $this->create_order_from_cart_with_status( OrderInternalStatus::PENDING );
				wc_reserve_stock_for_order( $order );
				$this->assertSame( 1, (int) wc_get_held_stock_quantity( $this->stock_product ), 'The pending order should hold its units.' );

				add_filter( 'woocommerce_hold_stock_for_checkout', '__return_false' );
				try {
					wc_reduce_stock_levels( $order );
				} finally {
					remove_filter( 'woocommerce_hold_stock_for_checkout', '__return_false' );
				}

				return (int) wc_get_held_stock_quantity( $this->stock_product );
			}
		);

		$this->assertSame( 0, $held );
	}

	/**
	 * @testdox A custom status added through woocommerce_reserved_stock_order_statuses counts its holds.
	 */
	public function test_stock_hold_counts_for_status_added_by_filter() {
		$add_status = function ( $statuses ) {
			$statuses[] = 'wc-awaiting-shipment';
			return $statuses;
		};
		add_filter( 'woocommerce_reserved_stock_order_statuses', $add_status );

		try {
			$held = $this->get_held_quantity_for_order_in_status( 'wc-awaiting-shipment' );
		} finally {
			remove_filter( 'woocommerce_reserved_stock_order_statuses', $add_status );
		}

		$this->assertSame( 2, $held, 'A status added through the filter should count its holds.' );
	}

	/**
	 * @testdox An unprefixed status added through woocommerce_reserved_stock_order_statuses counts its holds.
	 */
	public function test_stock_hold_counts_for_unprefixed_status_added_by_filter() {
		$add_status = function ( $statuses ) {
			$statuses[] = 'awaiting-shipment';
			return $statuses;
		};
		add_filter( 'woocommerce_reserved_stock_order_statuses', $add_status );

		try {
			$held = $this->get_held_quantity_for_order_in_status( 'wc-awaiting-shipment' );
		} finally {
			remove_filter( 'woocommerce_reserved_stock_order_statuses', $add_status );
		}

		$this->assertSame( 2, $held, 'An unprefixed status added through the filter should count its holds.' );
	}

	/**
	 * @testdox A woocommerce_query_for_reserved_stock callback that string-replaces the pre-11.3.1 status literal still works.
	 */
	public function test_stock_hold_counts_for_status_added_by_replacing_legacy_query_literal() {
		$replace_literal = function ( $query ) {
			return str_replace(
				"IN ( 'wc-checkout-draft', 'wc-pending' )",
				"IN ( 'wc-checkout-draft', 'wc-pending', 'wc-quote-open' )",
				$query
			);
		};
		add_filter( 'woocommerce_query_for_reserved_stock', $replace_literal );

		try {
			$held = $this->get_held_quantity_for_order_in_status( 'wc-quote-open' );
		} finally {
			remove_filter( 'woocommerce_query_for_reserved_stock', $replace_literal );
		}

		$this->assertSame( 2, $held, 'A status added by replacing the legacy status literal should count its holds.' );
	}

	/**
	 * @testdox An unusable return from woocommerce_reserved_stock_order_statuses falls back to the default list.
	 *
	 * @dataProvider invalid_stock_hold_statuses_provider
	 *
	 * @param callable $callback Filter callback returning an unusable value.
	 */
	public function test_stock_hold_statuses_filter_ignores_invalid_return( $callback ) {
		add_filter( 'woocommerce_reserved_stock_order_statuses', $callback );

		try {
			$held = $this->get_held_quantity_for_order_in_status( OrderInternalStatus::PROCESSING );
		} finally {
			remove_filter( 'woocommerce_reserved_stock_order_statuses', $callback );
		}

		$this->assertSame( 2, $held, 'An unusable filter return should leave the default statuses counted.' );
	}

	/**
	 * Filter returns that must not change which holds are counted.
	 *
	 * @return array[]
	 */
	public function invalid_stock_hold_statuses_provider() {
		return array(
			'non-array'        => array( '__return_false' ),
			'empty array'      => array( '__return_empty_array' ),
			'non-scalars only' => array(
				function () {
					return array( array(), new stdClass() );
				},
			),
		);
	}

	/**
	 * Statuses whose holds must count, and those whose holds must not.
	 *
	 * @return array[]
	 */
	public function stock_hold_status_provider() {
		return array(
			'checkout-draft' => array( 'wc-checkout-draft', true ),
			'pending'        => array( OrderInternalStatus::PENDING, true ),
			'processing'     => array( OrderInternalStatus::PROCESSING, true ),
			'on-hold'        => array( OrderInternalStatus::ON_HOLD, true ),
			'completed'      => array( OrderInternalStatus::COMPLETED, true ),
			'cancelled'      => array( OrderInternalStatus::CANCELLED, false ),
			'failed'         => array( OrderInternalStatus::FAILED, true ),
			'refunded'       => array( OrderInternalStatus::REFUNDED, false ),
			'trash'          => array( OrderStatus::TRASH, false ),
			'custom'         => array( 'wc-awaiting-shipment', false ),
		);
	}

	/**
	 * Stock on hand minus held stock, read fresh from the database.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	private function get_available_stock( $product_id ) {
		$product = wc_get_product( $product_id );

		return (int) ( $product->get_stock_quantity() - wc_get_held_stock_quantity( $product ) );
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
