<?php

use Automattic\WooCommerce\Internal\Utilities\LegacyRestApiStub;

/**
 * Unit tests for the WooCommerce class.
 */
class WooCommerce_Test extends \WC_Unit_Test_Case {

	/**
	 * The default URI.
	 *
	 * @var string
	 */
	private static $default_uri;

	/**
	 * Store the default URI.
	 *
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$default_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}


	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}


	/**
	 * Clean up request globals after each test so request-classification state does not leak.
	 */
	public function tearDown(): void {
		unset( $_GET['rest_route'] );
		parent::tearDown();
	}

	/**
	 * Restore the default URI.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$_SERVER['REQUEST_URI'] = self::$default_uri;
	}

	/**
	 * Test that the $api property is defined and holds an instance of LegacyRestApiStub
	 * (the Legacy REST API was removed in WooCommerce 9.0).
	 */
	public function test_api_property(): void {
		$this->assertInstanceOf( LegacyRestApiStub::class, WC()->api );
	}

	/**
	 * Test that the rest api returns false when it is not an rest api request.
	 */
	public function test_rest_api_returns_false() {
		$this->assertEquals( WC()->is_rest_api_request(), false );
	}

	/**
	 * Test that the rest api returns true when it is an rest api request.
	 */
	public function test_rest_api_returns_true() {
		// Set the request uri to a rest api request.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/v3/products';
		$this->assertEquals( WC()->is_rest_api_request(), true );
	}

	/**
	 * Test that REST requests routed via the `rest_route` query parameter (used with plain permalinks)
	 * are detected, even though the wp-json prefix is absent from the request URI.
	 */
	public function test_rest_api_returns_true_for_rest_route_query_param() {
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_GET['rest_route']     = '/wc/v3/products';
		$this->assertTrue( WC()->is_rest_api_request() );
	}

	/**
	 * Test that Store API requests routed via the `rest_route` query parameter are recognised as Store
	 * API requests (and therefore as REST requests).
	 */
	public function test_store_api_request_detected_via_rest_route_query_param() {
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_GET['rest_route']     = '/wc/store/v1/cart';
		$this->assertTrue( WC()->is_store_api_request() );
		$this->assertTrue( WC()->is_rest_api_request() );
	}

	/**
	 * Test that a non-Store-API request routed via the `rest_route` query parameter is a REST request
	 * but not a Store API request.
	 */
	public function test_non_store_rest_route_is_not_a_store_api_request() {
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_GET['rest_route']     = '/wc/v3/products';
		$this->assertFalse( WC()->is_store_api_request() );
		$this->assertTrue( WC()->is_rest_api_request() );
	}
}
