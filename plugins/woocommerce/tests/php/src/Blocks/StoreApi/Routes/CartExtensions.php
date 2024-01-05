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
}
