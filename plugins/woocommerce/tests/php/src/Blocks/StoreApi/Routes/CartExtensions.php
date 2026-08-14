<?php
/**
 * Cart extensions route tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

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
				'callback'  => function () {
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
	 * @testdox Invalid namespace errors expose the mapped Store API response without a context override.
	 */
	public function test_invalid_namespace(): void {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'namespace' => 'test-plugin',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status(), 'An invalid extension namespace should return HTTP 400.' );
		$this->assertSame( 'woocommerce_rest_cart_extensions_error', $data['code'], 'The response should use the mapped cart-extension error code.' );
		$this->assertSame( 'There is no such namespace registered: test-plugin.', $data['message'], 'The response should identify the missing namespace exactly.' );
		$this->assertSame( 400, $data['data']['status'], 'The response data should preserve the HTTP status.' );
		$this->assertArrayNotHasKey( 'context', $data['data'], 'The invalid-namespace response should leave notice context selection to the client.' );
	}

	/**
	 * @testdox Extension callback RouteException data passes through the public route unchanged.
	 */
	public function test_callback_route_exception_passes_through(): void {
		$extend            = StoreApi::container()->get( ExtendSchema::class );
		$original_callback = $extend->get_update_callback( 'valid-test-plugin' );

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'valid-test-plugin',
				'callback'  => function () {
					throw new RouteException(
						'test_error',
						'This is an error with cart context.',
						400,
						array( 'context' => 'wc/cart' )
					);
				},
			)
		);

		try {
			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params(
				array(
					'namespace' => 'valid-test-plugin',
				)
			);
			$response = rest_get_server()->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( 400, $response->get_status(), 'The callback RouteException should preserve its HTTP status.' );
			$this->assertSame( 'test_error', $data['code'], 'The callback RouteException should preserve its error code.' );
			$this->assertSame( 'This is an error with cart context.', $data['message'], 'The callback RouteException should preserve its message.' );
			$this->assertSame( 400, $data['data']['status'], 'The callback RouteException response data should preserve its status.' );
			$this->assertSame( 'wc/cart', $data['data']['context'], 'The callback RouteException should preserve its notice context.' );
		} finally {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => 'valid-test-plugin',
					'callback'  => $original_callback,
				)
			);
		}
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
