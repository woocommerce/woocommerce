<?php

namespace Automattic\WooCommerce\Tests\Blocks\Library;

use Automattic\WooCommerce\Blocks\Package;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test \Automattic\WooCommerce\Blocks\Domain\Services\Hydration class.
 */
class Hydration extends TestCase {

	/**
	 * System under test.
	 *
	 * @var \Automattic\WooCommerce\Blocks\Domain\Services\Hydration
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = Package::container()->get( \Automattic\WooCommerce\Blocks\Domain\Services\Hydration::class );
	}


	/**
	 * @testDox REST API response is returned without loading entire REST server.
	 */
	public function test_rest_api_response_data_from_store_api() {
		$path = '/wc/store/v1/cart';

		$api_loaded = false;
		add_action(
			'rest_api_init',
			function () use ( &$api_loaded ) {
				$api_loaded = true;
			}
		);

		$request_callback_filter_called = false;
		add_filter(
			'woocommerce_hydration_request_after_callbacks',
			function ( $response ) use ( &$request_callback_filter_called ) {
				$request_callback_filter_called = true;
				return $response;
			}
		);

		$dispatch_filter_called = false;
		add_filter(
			'woocommerce_hydration_dispatch_request',
			function ( $response ) use ( &$dispatch_filter_called ) {
				$dispatch_filter_called = true;
				return $response;
			}
		);

		$response = $this->sut->get_rest_api_response_data( $path );

		$this->assertFalse( $api_loaded );
		$this->assertTrue( $request_callback_filter_called );
		$this->assertTrue( $dispatch_filter_called );

		$this->assertArrayHasKey( 'body', $response );
		$this->assertArrayHasKey( 'headers', $response );

		// Verify few keys from headers and body.
		$this->assertArrayHasKey( 'Cart-Token', $response['headers'] );
		$this->assertArrayHasKey( 'items', $response['body'] );
		$this->assertArrayHasKey( 'coupons', $response['body'] );
	}

	/**
	 * @testDox Controllers outside store API are not supported for hydration.
	 */
	public function test_rest_api_response_data_outside_store_api() {
		$path = '/wc/orders';

		$response = $this->sut->get_rest_api_response_data( $path );

		$this->assertEmpty( $response );
	}

	/**
	 * @testDox Store API controllers that don't implement GET methods should not return anything.
	 */
	public function test_rest_api_response_data_no_get_handler() {
		$path = '/wc/store/v1/checkout/1';

		$response = $this->sut->get_rest_api_response_data( $path );

		$this->assertEmpty( $response );
	}
}
