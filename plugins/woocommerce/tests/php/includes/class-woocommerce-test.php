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
	 * Test `maybe_halt_cron_activity_for_this_request`: sensitive page handling branch disables cron.
	 */
	public function test_maybe_halt_cron_activity_for_this_request_on_pages(): void {
		$this->assertIsInteger( has_action( 'init', 'wp_cron' ) );
		$_SERVER['REQUEST_URI'] = '/';

		$_GET['page_id'] = (string) wc_get_page_id( 'shop' );
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertIsInteger( has_action( 'init', 'wp_cron' ) );

		$_GET['page_id'] = (string) wc_get_page_id( 'checkout' );
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertFalse( has_action( 'init', 'wp_cron' ) );
		add_action( 'init', 'wp_cron' );

		unset( $_GET['page_id'] );

		$_SERVER['REQUEST_URI'] = '/2026/02/17/shop';
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertIsInteger( has_action( 'init', 'wp_cron' ) );

		$_SERVER['REQUEST_URI'] = '/2026/02/17/checkout';
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertFalse( has_action( 'init', 'wp_cron' ) );
		add_action( 'init', 'wp_cron' );

		unset( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Test `maybe_halt_cron_activity_for_this_request`: ajax/rest handling branch disables cron.
	 */
	public function test_maybe_halt_cron_activity_for_this_request_on_rest_and_ajax(): void {
		$this->assertIsInteger( has_action( 'init', 'wp_cron' ) );

		// `wp_is_serving_rest_request` has no filter, hence we'll test with AJAX-handling branch only (same branch).
		// Verify if WooCommerce REST URI validation is correct instead.
		$this->assertTrue( false !== strpos( '/wp-json/wc/v1/orders', trim( rest_get_url_prefix(), '/' ) . '/wc/' ) );
		$this->assertFalse( false !== strpos( '/wp-json/wp/v2/users', trim( rest_get_url_prefix(), '/' ) . '/wc/' ) );

		$_SERVER['REQUEST_URI'] = '/';

		add_filter( 'wp_doing_ajax', '__return_false' );
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertIsInteger( has_action( 'init', 'wp_cron' ) );
		remove_filter( 'wp_doing_ajax', '__return_false' );

		add_filter( 'wp_doing_ajax', '__return_true' );
		WC()->maybe_halt_cron_activity_for_this_request();
		$this->assertFalse( has_action( 'init', 'wp_cron' ) );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		add_action( 'init', 'wp_cron' );
		unset( $_SERVER['REQUEST_URI'] );
	}
}
