<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * Tests for OrderController class.
 *
 * Note: `woocommerce_checkout_customer_id` filter tests live at the integration
 * level in Checkout::process_customer(). The tests below cover the OrderController
 * methods that back the draft-order sync path.
 */
class OrderControllerTest extends \WC_Unit_Test_Case {

	/**
	 * The OrderController instance under test.
	 *
	 * @var OrderController
	 */
	private OrderController $controller;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wc_empty_cart();
		$this->controller = new OrderController();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_checkout_customer_id' );
	}

	/**
	 * Regression guard: update_order_from_cart() must NOT apply
	 * woocommerce_checkout_customer_id so the filter fires exactly once — at
	 * Checkout::process_customer() time — rather than on every cart mutation.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\Checkout::process_customer()
	 * @see WC_Checkout::process_customer()
	 */
	public function test_update_order_from_cart_does_not_apply_checkout_customer_id_filter() {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$filter_called = false;
		add_filter(
			'woocommerce_checkout_customer_id',
			function ( $id ) use ( &$filter_called ) {
				$filter_called = true;
				return $id;
			}
		);

		$order = new \WC_Order();
		$this->controller->update_order_from_cart( $order );

		$this->assertFalse(
			$filter_called,
			'woocommerce_checkout_customer_id must not fire during cart-sync (update_order_from_cart); it must only fire once in Checkout::process_customer() at submission time.'
		);

		// Logged-in user ID still lands on the order (the raw current-user call).
		$this->assertSame( $user_id, $order->get_customer_id() );
	}
}
