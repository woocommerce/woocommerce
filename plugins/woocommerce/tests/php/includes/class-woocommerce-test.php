<?php

declare( strict_types = 1 );

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
	 * Restore the request globals after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['rest_route'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		parent::tearDown();
	}

	/**
	 * @testdox Should return false when the request is not a Store API request.
	 */
	public function test_is_store_api_request_returns_false_for_non_store_request(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/options';

		$this->assertFalse( WC()->is_store_api_request(), 'A non-Store API WooCommerce REST request should not be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request that uses pretty permalinks.
	 */
	public function test_is_store_api_request_returns_true_for_pretty_permalinks(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A /wp-json/wc/store/ path should be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request that uses plain permalinks (?rest_route=).
	 */
	public function test_is_store_api_request_returns_true_for_plain_permalinks(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc/store/v1/cart';
		$_GET['rest_route']     = '/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ?rest_route=/wc/store/ request should be detected as Store API.' );
	}

	/**
	 * @testdox Should not detect a non-Store API plain-permalink request as Store API.
	 */
	public function test_is_store_api_request_returns_false_for_plain_permalinks_non_store(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc-admin/options';
		$_GET['rest_route']     = '/wc-admin/options';

		$this->assertFalse( WC()->is_store_api_request(), 'A non-Store API ?rest_route= request should not be detected as Store API.' );
	}

	/**
	 * @testdox Should not detect a Store-API-like value in a query argument as a Store API request.
	 */
	public function test_is_store_api_request_returns_false_for_rest_like_query_arg(): void {
		$_SERVER['REQUEST_URI'] = '/some-page/?arg=/wp-json/wc/store/v1/cart';

		$this->assertFalse( WC()->is_store_api_request(), 'A REST-like value in a query argument should not be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request whose URL has repeated leading slashes.
	 */
	public function test_is_store_api_request_returns_true_for_repeated_slashes(): void {
		$_SERVER['REQUEST_URI'] = '///wp-json/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ///wp-json/wc/store/ path with repeated leading slashes should be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API plain-permalink request whose route has repeated leading slashes.
	 */
	public function test_is_store_api_request_returns_true_for_repeated_slashes_plain(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=//wc/store/v1/cart';
		$_GET['rest_route']     = '//wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ?rest_route=//wc/store/ request with repeated leading slashes should be detected as Store API.' );
	}
}
