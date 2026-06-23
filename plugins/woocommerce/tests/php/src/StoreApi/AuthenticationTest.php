<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WC_Unit_Test_Case;

/**
 * Tests for the StoreApi Authentication class.
 */
class AuthenticationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Authentication
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Authentication();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'], $_SERVER['HTTP_ORIGIN'], $_GET['rest_route'] );
		parent::tearDown();
	}

	/**
	 * @testdox maybe_use_store_api_session_handler returns the Store API session handler for a valid cart token.
	 */
	public function test_returns_session_handler_for_valid_cart_token(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( 'test_customer' );

		$result = $this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( SessionHandler::class, $result, 'A valid cart token should select the Store API session handler' );
	}

	/**
	 * @testdox maybe_use_store_api_session_handler returns the default handler for an invalid cart token.
	 */
	public function test_returns_default_handler_for_invalid_cart_token(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = 'not-a-valid-token';

		$result = $this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( 'WC_Session_Handler', $result, 'An invalid cart token should leave the default session handler in place' );
	}

	/**
	 * @testdox A valid cart token is memoized on the instance so it is validated once per token.
	 */
	public function test_cart_token_validity_is_memoized(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$token                      = CartTokenUtils::get_cart_token( 'test_customer' );
		$_SERVER['HTTP_CART_TOKEN'] = $token;

		$this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( $token, $this->read_private( 'validated_cart_token' ), 'The validated token should be remembered on the instance' );
		$this->assertTrue( $this->read_private( 'validated_cart_token_is_valid' ), 'A valid cart token should be remembered as valid' );
	}

	/**
	 * @testdox An invalid cart token is memoized as invalid.
	 */
	public function test_invalid_cart_token_is_memoized_as_invalid(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = 'not-a-valid-token';

		$this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( 'not-a-valid-token', $this->read_private( 'validated_cart_token' ), 'The validated token should be remembered on the instance' );
		$this->assertFalse( $this->read_private( 'validated_cart_token_is_valid' ), 'An invalid cart token should be remembered as invalid' );
	}

	/**
	 * @testdox An empty cart token returns the default handler and is not validated or memoized.
	 */
	public function test_returns_default_handler_for_empty_cart_token(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = '';

		$result = $this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( 'WC_Session_Handler', $result, 'An empty cart token should leave the default session handler in place' );
		$this->assertNull( $this->read_private( 'validated_cart_token' ), 'An empty cart token should not populate the validation memo' );
	}

	/**
	 * @testdox send_cors_headers allows any origin for a valid cart token and reuses the memoized validation.
	 */
	public function test_send_cors_headers_allows_origin_for_valid_cart_token(): void {
		$token                  = CartTokenUtils::get_cart_token( 'cors_customer' );
		$_SERVER['HTTP_ORIGIN'] = 'https://foreign-origin.test';

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$request->set_header( 'Cart-Token', $token );

		$server = $this->make_header_recording_server();

		$this->sut->send_cors_headers( true, null, $request, $server );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $server->headers, 'A valid cart token should allow the request origin' );
		$this->assertSame( 'https://foreign-origin.test', $server->headers['Access-Control-Allow-Origin'], 'The allowed origin should echo the request origin' );
		$this->assertSame( $token, $this->read_private( 'validated_cart_token' ), 'send_cors_headers should populate the shared validation memo' );
	}

	/**
	 * @testdox send_cors_headers does not allow a foreign origin for an invalid cart token.
	 */
	public function test_send_cors_headers_blocks_foreign_origin_for_invalid_cart_token(): void {
		$_SERVER['HTTP_ORIGIN'] = 'https://foreign-origin.test';

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$request->set_header( 'Cart-Token', 'not-a-valid-token' );

		$server = $this->make_header_recording_server();

		$this->sut->send_cors_headers( true, null, $request, $server );

		$this->assertArrayNotHasKey( 'Access-Control-Allow-Origin', $server->headers, 'An invalid cart token must not grant cross-origin access' );
	}

	/**
	 * @testdox A token validated for session-handler selection is reused by send_cors_headers from the same slot.
	 */
	public function test_cart_token_validation_is_shared_across_call_sites(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$token                      = CartTokenUtils::get_cart_token( 'shared_customer' );
		$_SERVER['HTTP_CART_TOKEN'] = $token;
		$_SERVER['HTTP_ORIGIN']     = 'https://foreign-origin.test';

		$this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );
		$this->assertSame( $token, $this->read_private( 'validated_cart_token' ), 'The session-handler call site should populate the memo' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$request->set_header( 'Cart-Token', $token );
		$server = $this->make_header_recording_server();

		$this->sut->send_cors_headers( true, null, $request, $server );

		$this->assertSame( $token, $this->read_private( 'validated_cart_token' ), 'The CORS call site should reuse the same slot for the same token' );
		$this->assertTrue( $this->read_private( 'validated_cart_token_is_valid' ), 'The shared slot should hold the valid result' );
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $server->headers, 'CORS should be granted from the shared validation result' );
	}

	/**
	 * @testdox The memo re-validates when a different cart token is presented.
	 */
	public function test_cart_token_memo_revalidates_on_token_change(): void {
		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = 'not-a-valid-token';

		$this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );
		$this->assertSame( 'not-a-valid-token', $this->read_private( 'validated_cart_token' ), 'The invalid token should be remembered' );
		$this->assertFalse( $this->read_private( 'validated_cart_token_is_valid' ), 'The invalid token should be remembered as invalid' );

		$valid                      = CartTokenUtils::get_cart_token( 'change_customer' );
		$_SERVER['HTTP_CART_TOKEN'] = $valid;

		$result = $this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( SessionHandler::class, $result, 'A different valid token should be re-validated and select the Store API handler' );
		$this->assertSame( $valid, $this->read_private( 'validated_cart_token' ), 'The slot should update to the new token' );
		$this->assertTrue( $this->read_private( 'validated_cart_token_is_valid' ), 'The new token should be remembered as valid' );
	}

	/**
	 * @testdox A cart token cached as valid is re-validated (and rejected) once it has expired.
	 */
	public function test_expired_cart_token_is_revalidated_when_memo_has_expired(): void {
		// A correctly signed token that is already expired.
		$expired = JsonWebToken::create(
			array(
				'user_id' => 't_expired',
				'exp'     => time() - HOUR_IN_SECONDS,
				'iss'     => 'store-api',
			),
			'@' . wp_salt()
		);

		// Simulate a slot that cached this token as valid before it expired.
		$this->set_private( 'validated_cart_token', $expired );
		$this->set_private( 'validated_cart_token_is_valid', true );
		$this->set_private( 'validated_cart_token_exp', time() - HOUR_IN_SECONDS );

		$_GET['rest_route']         = '/wc/store/v1/cart';
		$_SERVER['HTTP_CART_TOKEN'] = $expired;

		$result = $this->sut->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

		$this->assertSame( 'WC_Session_Handler', $result, 'A cached-valid token that has since expired must be re-validated and rejected' );
		$this->assertFalse( $this->read_private( 'validated_cart_token_is_valid' ), 'The slot should be refreshed to invalid after re-validation' );
	}

	/**
	 * Sets a private property on the system under test.
	 *
	 * @param string $property Property name.
	 * @param mixed  $value    Value to set.
	 */
	private function set_private( string $property, $value ): void {
		$ref = new \ReflectionProperty( $this->sut, $property );
		$ref->setAccessible( true );
		$ref->setValue( $this->sut, $value );
	}

	/**
	 * Builds a lightweight object that records headers passed to send_header().
	 *
	 * @return object
	 */
	private function make_header_recording_server() {
		return new class() {
			/**
			 * Recorded headers.
			 *
			 * @var array<string, mixed>
			 */
			public $headers = array();

			/**
			 * Record a header.
			 *
			 * @param string $key   Header name.
			 * @param mixed  $value Header value.
			 */
			public function send_header( $key, $value ) {
				$this->headers[ $key ] = $value;
			}
		};
	}

	/**
	 * Reads a private property from the system under test.
	 *
	 * @param string $property Property name.
	 * @return mixed
	 */
	private function read_private( string $property ) {
		$ref = new \ReflectionProperty( $this->sut, $property );
		$ref->setAccessible( true );
		return $ref->getValue( $this->sut );
	}
}
