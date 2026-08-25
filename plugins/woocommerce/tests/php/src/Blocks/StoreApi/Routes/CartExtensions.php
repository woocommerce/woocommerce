<?php
/**
 * Cart extensions route tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

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
		$order = wc_create_order();
		$order->add_product( $this->product, 1 );

		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_instance_id( 1 );
		$shipping_item->set_total( 10 );
		$order->add_item( $shipping_item );

		$order->set_status( 'pending' );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->update_meta_data( '_shipping_hash', 'hash-from-calculated-shipping' );
		$order->calculate_totals();
		$order->save();

		wc()->session->set( 'store_api_draft_order', $order->get_id() );
		wc()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		// Match a new request in which shipping has not been calculated yet.
		$shipping_methods = new \ReflectionProperty( \WC_Cart::class, 'shipping_methods' );
		$shipping_methods->setAccessible( true );
		$shipping_methods->setValue( wc()->cart, null );
		wc()->shipping()->reset_shipping();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'Nonce', 'invalid' );
		$request->set_body_params(
			array(
				'namespace' => 'valid-test-plugin',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );

		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $reloaded_order->get_items( 'shipping' ) );
		$this->assertEquals( 20.0, (float) $reloaded_order->get_total() );
	}
}
