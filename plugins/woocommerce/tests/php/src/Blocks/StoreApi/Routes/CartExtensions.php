<?php
/**
 * Cart extensions route tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Cart Controller Tests.
 */
class CartExtensions extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);

		wc_empty_cart();

		wc()->cart->add_to_cart( $this->product->get_id(), 1 );

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'valid-test-plugin',
				'callback'  => function() {
					add_action(
						'woocommerce_cart_calculate_fees',
						function() {
							wc()->cart->add_fee( 'Surcharge', 10, true, 'standard' );
						}
					);
				},
			)
		);
	}
	/**
	 * Test getting cart with invalid namespace.
	 */
	public function test_invalid_namespace() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'namespace' => 'test-plugin',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test getting cart with invalid namespace.
	 */
	public function test_cart_being_updated() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'namespace' => 'valid-test-plugin',
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					'total_fees' => '1000',
				),
			)
		);
	}

	/**
	 * A rejected cart update must not sync the cart to a pending order.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/68007
	 */
	public function test_rejected_cart_update_does_not_remove_shipping_from_pending_order() {
		$order = $this->create_pending_order_with_shipping();
		$this->reset_shipping_calculation();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'Nonce', 'invalid' );
		$request->set_body_params(
			array(
				'namespace' => 'valid-test-plugin',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
		$this->assertPendingOrderRetainsShipping( $order );
	}

	/**
	 * A successful no-op cart update must not sync uncalculated shipping to a pending order.
	 *
	 * @dataProvider needs_shipping_filter_provider
	 * @see https://github.com/woocommerce/woocommerce/issues/68007
	 *
	 * @param bool $filter_needs_shipping Whether to filter the cart to not need shipping.
	 */
	public function test_successful_no_op_cart_update_does_not_remove_shipping_from_pending_order( $filter_needs_shipping ) {
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$order = $this->create_pending_order_with_shipping();
		$this->reset_shipping_calculation();

		$needs_shipping_filter = static function () {
			return false;
		};

		if ( $filter_needs_shipping ) {
			add_filter( 'woocommerce_cart_needs_shipping', $needs_shipping_filter, 999 );
		}

		try {
			$this->assertSame( ! $filter_needs_shipping, wc()->cart->needs_shipping() );
			$this->assertFalse( wc()->cart->has_calculated_shipping() );
			$this->assertNull( wc()->cart->get_shipping_methods() );
			$this->assertSame( array(), wc()->shipping()->get_packages() );

			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params(
				array(
					'key' => array_key_first( wc()->cart->get_cart() ),
				)
			);

			$response = rest_get_server()->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$this->assertFalse( wc()->cart->has_calculated_shipping() );
			$this->assertSame( array(), wc()->shipping()->get_packages() );
		} finally {
			remove_filter( 'woocommerce_cart_needs_shipping', $needs_shipping_filter, 999 );
		}

		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertSame( 'hash-from-calculated-shipping', $reloaded_order->get_meta( '_shipping_hash' ) );
		$this->assertPendingOrderRetainsShipping( $order );
	}

	/**
	 * Data provider for carts whose shipping requirement may be filtered.
	 *
	 * @return array
	 */
	public function needs_shipping_filter_provider() {
		return array(
			'cart needs shipping'           => array( false ),
			'needs shipping filtered false' => array( true ),
		);
	}

	/**
	 * Create a pending order that matches the current cart and contains shipping.
	 *
	 * @return \WC_Order
	 */
	private function create_pending_order_with_shipping() {
		$order = wc_create_order();
		$order->add_product( $this->product, 1 );

		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_instance_id( 1 );
		$shipping_item->set_total( 10 );
		$order->add_item( $shipping_item );

		$order->set_status( OrderStatus::PENDING );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->update_meta_data( '_shipping_hash', 'hash-from-calculated-shipping' );
		$order->calculate_totals();
		$order->save();

		wc()->session->set( 'store_api_draft_order', $order->get_id() );
		wc()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		return $order;
	}

	/**
	 * Reset shipping state to match a new request that has not calculated shipping.
	 */
	private function reset_shipping_calculation() {
		$shipping_methods = new \ReflectionProperty( \WC_Cart::class, 'shipping_methods' );
		$shipping_methods->setAccessible( true );
		$shipping_methods->setValue( wc()->cart, null );

		$has_calculated_shipping = new \ReflectionProperty( \WC_Cart::class, 'has_calculated_shipping' );
		$has_calculated_shipping->setAccessible( true );
		$has_calculated_shipping->setValue( wc()->cart, false );

		wc()->shipping()->reset_shipping();
	}

	/**
	 * Assert that a pending order retains its shipping line and total.
	 *
	 * @param \WC_Order $order Order to reload and inspect.
	 */
	private function assertPendingOrderRetainsShipping( $order ) {
		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $reloaded_order->get_items( OrderItemType::SHIPPING ) );
		$this->assertEquals( 20.0, (float) $reloaded_order->get_total() );
	}
}
