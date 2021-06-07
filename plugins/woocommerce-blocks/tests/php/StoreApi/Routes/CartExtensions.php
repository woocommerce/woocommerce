<?php
/**
 * Cart extensions route tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Controllers;

use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use \WC_Helper_Coupon as CouponHelper;
use \WC_Helper_Shipping;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;
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
 * Cart Controller Tests.
 */
class CartExtensions extends TestCase {

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

		wc_empty_cart();

		$this->keys   = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
		wc()->cart->apply_coupon( $this->coupon->get_code() );

		WC_Helper_Shipping::create_simple_flat_rate();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/extensions', $routes );
	}

	/**
	 * Test getting cart with invalid namespace.
	 */
	public function test_post() {
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/extensions' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'namespace' => 'test-plugin',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}
}
