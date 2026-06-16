<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\RoutesController;

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
	 * Callback stored so tearDown can call remove_filter() with the exact
	 * callable, rather than remove_all_filters() which would clear every
	 * callback on the hook and could break other tests running in the same
	 * PHP process.
	 *
	 * @var callable|null
	 */
	private $customer_id_filter_cb = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wc_empty_cart();
		$this->controller          = new OrderController();
		$this->customer_id_filter_cb = null;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( null !== $this->customer_id_filter_cb ) {
			remove_filter( 'woocommerce_checkout_customer_id', $this->customer_id_filter_cb );
			$this->customer_id_filter_cb = null;
		}
		parent::tearDown();
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

		$filter_called             = false;
		$this->customer_id_filter_cb = function ( $id ) use ( &$filter_called ) {
			$filter_called = true;
			return $id;
		};
		add_filter( 'woocommerce_checkout_customer_id', $this->customer_id_filter_cb );

		$order = new \WC_Order();
		$this->controller->update_order_from_cart( $order );

		$this->assertFalse(
			$filter_called,
			'woocommerce_checkout_customer_id must not fire during cart-sync (update_order_from_cart); it must only fire once in Checkout::process_customer() at submission time.'
		);

		// Logged-in user ID still lands on the order (the raw current-user call).
		$this->assertSame( $user_id, $order->get_customer_id() );
	}

	/**
	 * Regression guard: Checkout::process_customer() MUST apply
	 * woocommerce_checkout_customer_id and write the filtered customer ID to
	 * the order. Removing the apply_filters() call from production code must
	 * flip this assertion from green to red.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\Checkout::process_customer()
	 */
	public function test_process_customer_applies_checkout_customer_id_filter() {
		// $user_a is the logged-in user; $user_b_id is what the filter overrides to.
		$user_a_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$user_b_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_a_id );

		$this->customer_id_filter_cb = static function () use ( $user_b_id ) {
			return $user_b_id;
		};
		add_filter( 'woocommerce_checkout_customer_id', $this->customer_id_filter_cb );

		// Create a draft order and inject it directly into the Checkout route so
		// get_order_or_throw() can return it without a full REST request.
		$order          = \WC_Helper_Order::create_order( $user_a_id );
		$checkout_route = StoreApi::container()->get( RoutesController::class )->get( 'checkout' );

		$order_prop = new \ReflectionProperty( $checkout_route, 'order' );
		$order_prop->setAccessible( true );
		$order_prop->setValue( $checkout_route, $order );

		$method = new \ReflectionMethod( $checkout_route, 'process_customer' );
		$method->setAccessible( true );
		$method->invoke( $checkout_route, new \WP_REST_Request() );

		$this->assertSame(
			$user_b_id,
			$order->get_customer_id(),
			'woocommerce_checkout_customer_id filter must be applied in Checkout::process_customer(), setting the order customer to the filtered value.'
		);
	}
}
