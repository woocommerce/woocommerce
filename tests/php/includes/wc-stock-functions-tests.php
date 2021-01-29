<?php
/**
 * Unit tests for wc-stock-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

/**
 * Class WC_Stock_Functions_Tests.
 */
class WC_Stock_Functions_Tests extends \WC_Unit_Test_Case {

	/**
	 * @var array List of statuses which reduces stock from inventory.
	 */
	public $order_stock_reduce_statuses = array(
		'wc-processing',
		'wc-completed',
		'wc-on-hold',
	);

	/**
	 * @var array List of statuses which restores stock back into inventory.
	 */
	public $order_stock_restore_statuses = array(
		'wc-cancelled',
		'wc-pending',
	);

	/**
	 * @var array List of statuses which have no impact on inventory.
	 */
	public $order_stock_no_effect_statuses = array(
		'wc-failed',
		'wc-refunded',
	);

	/**
	 * Helper function to simulate creating order from cart.
	 *
	 * @param string $status Status for the newly created order.
	 */
	private function create_order_from_cart_with_status( $status ) {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
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
	 * Test inventory count after order status transtions which reduces stock to another status which also reduces stock.
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
	 * Test inventory count after order status transtions which reduces stock to another status which restores stock.
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
	 * Test inventory count after order status transtions which reduces stock to another status which don't affect inventory.
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
	 * Test inventory count after order status transtions which restores stock to another status which reduces stock.
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
	 * Test inventory count after order status transtions which restores stock to another status which also restores stock.
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
	 * Test inventory count after order status transtions which restores stock to another status which don't affect inventory.
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
	 * Test inventory count after order status transtions which don't affect inventory stock to another status which reduces stock.
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
	 * Test inventory count after order status transtions which don't affect inventory stock to another status which restores stock.
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
	 * Test inventory count after order status transtions which don't affect inventory stock to another status which also don't affect inventory.
	 * Stock levels will not change before or after the transition.
	 */
	public function test_status_transition_stock_no_effect_to_stock_no_effect() {
		foreach ( $this->order_stock_no_effect_statuses as $order_status_from ) {
			foreach ( $this->order_stock_no_effect_statuses as $order_status_to ) {
				$this->transition_order_status_and_assert_stock_quantity( $order_status_from, $order_status_to, 10, 10 );
			}
		}
	}
}
