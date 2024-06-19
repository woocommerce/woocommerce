<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

/**
 * ControllerTests
 */
class ControllerTests extends \WP_Test_REST_TestCase {
	/**
	 * Setup Rest API server.
	 */
	protected function setUp(): void {
		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Test v1 route registration.
	 */
	public function test_v1_routes() {
		$routes     = rest_get_server()->get_routes();
		$namespace  = '/wc/store/v1';
		$test_paths = array(
			'/batch',
			'/cart',
			'/cart/add-item',
			'/cart/apply-coupon',
			'/cart/coupons',
			'/cart/coupons/(?P<code>[\w-]+)',
			'/cart/extensions',
			'/cart/items',
			'/cart/items/(?P<key>[\w-]{32})',
			'/cart/remove-coupon',
			'/cart/remove-item',
			'/cart/select-shipping-rate',
			'/cart/update-customer',
			'/cart/update-item',
			'/checkout',
			'/products/attributes',
			'/products/attributes/(?P<id>[\d]+)',
			'/products/attributes/(?P<attribute_id>[\d]+)/terms',
			'/products/categories',
			'/products/categories/(?P<id>[\d]+)',
			'/products/collection-data',
			'/products/reviews',
			'/products',
			'/products/(?P<id>[\d]+)',
			'/products/tags',
		);
		foreach ( $test_paths as $test_path ) {
			$this->assertArrayHasKey( $namespace . $test_path, $routes );
		}
	}

	/**
	 * Test unversioned route registration.
	 */
	public function test_unversioned_routes() {
		$routes     = rest_get_server()->get_routes();
		$namespace  = '/wc/store/v1';
		$test_paths = array(
			'/batch',
			'/cart',
			'/cart/add-item',
			'/cart/apply-coupon',
			'/cart/coupons',
			'/cart/coupons/(?P<code>[\w-]+)',
			'/cart/extensions',
			'/cart/items',
			'/cart/items/(?P<key>[\w-]{32})',
			'/cart/remove-coupon',
			'/cart/remove-item',
			'/cart/select-shipping-rate',
			'/cart/update-customer',
			'/cart/update-item',
			'/checkout',
			'/products/attributes',
			'/products/attributes/(?P<id>[\d]+)',
			'/products/attributes/(?P<attribute_id>[\d]+)/terms',
			'/products/categories',
			'/products/categories/(?P<id>[\d]+)',
			'/products/collection-data',
			'/products/reviews',
			'/products',
			'/products/(?P<id>[\d]+)',
			'/products/tags',
		);
		foreach ( $test_paths as $test_path ) {
			$this->assertArrayHasKey( $namespace . $test_path, $routes );
		}
	}
}
