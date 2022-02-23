<?php
/**
 * Cart extensions route tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Controller Tests.
 */
class CartExtensions extends ControllerTestCase {

	/**
	 * Test getting cart with invalid namespace.
	 */
	public function test_post() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/extensions' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
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
}
