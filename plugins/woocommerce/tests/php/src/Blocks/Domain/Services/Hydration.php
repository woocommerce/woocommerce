<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Package;

/**
 * Test \Automattic\WooCommerce\Blocks\Domain\Services\Hydration class.
 */
class Hydration extends \WC_Unit_Test_Case {

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

	/**
	 * @testDox Hydrating a cart route leaves the cart context as it found it.
	 */
	public function test_cart_context_is_restored_after_hydration() {
		// Set the context explicitly rather than reading whatever the previous test left behind:
		// this is the state a front-end render starts in, and the assertion below is only
		// meaningful against a known starting point.
		WC()->cart->cart_context = 'shortcode';

		$this->sut->get_rest_api_response_data( '/wc/store/v1/cart' );

		$this->assertSame(
			'shortcode',
			WC()->cart->cart_context,
			'Hydration must not leak the store-api cart context into the surrounding request.'
		);
	}

	/**
	 * @testDox Hydration restores the cart context, store notices, and nonce check even when dispatching throws a non-Exception error.
	 */
	public function test_state_is_restored_when_hydration_throws_a_non_exception_error(): void {
		WC()->cart->cart_context = 'shortcode';
		wc_clear_notices();
		wc_add_notice( 'Notice set before hydration.' );

		// Throwing from this filter fails the dispatch after `load_cart()` has already switched the cart
		// context to `store-api`, so the restore-on-error path is exercised against genuinely polluted state.
		// An `\Error` (not an `\Exception`) bypasses the catch inside `get_rest_api_response_data()`.
		$throwing_callback = function () {
			throw new \Error( 'Simulated non-Exception failure during hydration.' );
		};
		// @phpstan-ignore return.missing (The callback never returns by design: it simulates a fatal error during dispatch.)
		add_filter( 'woocommerce_hydration_request_after_callbacks', $throwing_callback );

		// @phpstan-ignore deadCode.unreachable (PHPStan considers the code after registering an always-throwing callback unreachable; at runtime the callback only fires during dispatch below.)
		$caught = null;
		try {
			$this->sut->get_rest_api_response_data( '/wc/store/v1/cart' );
		} catch ( \Error $error ) {
			$caught = $error;
		} finally {
			remove_filter( 'woocommerce_hydration_request_after_callbacks', $throwing_callback );
		}

		$this->assertInstanceOf(
			\Error::class,
			$caught,
			'Hydration should restore state but not swallow non-Exception errors.'
		);
		$this->assertSame(
			'shortcode',
			WC()->cart->cart_context,
			'Hydration must restore the cart context even when dispatching throws.'
		);
		$this->assertSame(
			array( 'Notice set before hydration.' ),
			wp_list_pluck( wc_get_notices( 'success' ), 'notice' ),
			'Hydration must restore store notices even when dispatching throws.'
		);
		$this->assertFalse(
			has_filter( 'woocommerce_store_api_disable_nonce_check', array( $this->sut, 'disable_nonce_check_callback' ) ),
			'Hydration must re-enable the nonce check even when dispatching throws.'
		);

		wc_clear_notices();
	}
}
