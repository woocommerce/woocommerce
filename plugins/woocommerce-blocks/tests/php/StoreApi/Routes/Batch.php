<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use \WC_Helper_Coupon as CouponHelper;
use \WC_Helper_Shipping;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Package as DomainPackage;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\Blocks\Domain\Services\FeatureGating;

/**
 * Batch Controller Tests.
 */
class Batch extends TestCase {

	private $mock_extend;

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );
		update_option( 'woocommerce_weight_unit', 'g' );

		$this->products = [];
		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend = new ExtendRestApi( new DomainPackage( '', '', new FeatureGating( 2 ) ), $formatters );

		// Create some test products.
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_weight( 10 );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_weight( 10 );
		$this->products[1]->set_regular_price( 10 );
		$this->products[1]->save();

		$this->coupon = CouponHelper::create_coupon();

		WC_Helper_Shipping::create_simple_flat_rate();
		wc_empty_cart();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/batch', $routes );
	}

	/**
	 * Test that a batch of requests are successful.
	 */
	public function test_success_cart_route_batch() {
		$request  = new WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[0]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
				),
			)
		);
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		// Assert that there were 2 successful results from the batch.
		$this->assertEquals( 2, count( $response_data['responses'] ) );
		$this->assertEquals( 201, $response_data['responses'][0]['status'] );
		$this->assertEquals( 201, $response_data['responses'][1]['status'] );
	}

	/**
	 * Test for a mixture of successful and non-successful requests in a batch.
	 */
	public function test_mix_cart_route_batch() {
		$request  = new WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => 99,
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
				),
			)
		);
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 2, count( $response_data['responses'] ) );
		$this->assertEquals( 400, $response_data['responses'][0]['status'], $response_data['responses'][0]['status'] );
		$this->assertEquals( 201, $response_data['responses'][1]['status'], $response_data['responses'][1]['status'] );
	}

	/**
	 * Get Requests not supported by batch.
	 */
	public function test_get_cart_route_batch() {
		$request  = new WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path' => '/wc/store/cart',
						'body' => array(
							'id' => 99,
							'quantity' => 1,
						),
					),
				),
			)
		);
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $response_data['code'], print_r( $response_data, true ) );
	}
}
