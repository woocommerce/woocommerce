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
	 * @testdox Should load WooCommerce includes in post editor load actions.
	 */
	public function test_loads_woocommerce_includes_for_post_editor_load_actions(): void {
		$this->assertSame(
			10,
			has_action( 'load-post.php', array( WC(), 'includes' ) ),
			'Existing post editor requests should invoke WooCommerce includes before block rendering.'
		);
		$this->assertSame(
			10,
			has_action( 'load-post-new.php', array( WC(), 'includes' ) ),
			'New post editor requests should invoke WooCommerce includes before block rendering.'
		);

		$original_query     = WC()->query;
		$original_screen    = $GLOBALS['current_screen'] ?? null;
		WC()->query         = null;
		$query_after_action = null;
		set_current_screen( 'post' );

		try {
			$this->assertTrue( is_admin(), 'New post editor load action should run in an admin context.' );
			// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment, WordPress.NamingConventions.ValidHookName.UseUnderscores
			do_action( 'load-post-new.php' );
			// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment, WordPress.NamingConventions.ValidHookName.UseUnderscores
			$query_after_action = WC()->query;
		} finally {
			WC()->query                = $original_query;
			$GLOBALS['current_screen'] = $original_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertInstanceOf(
			WC_Query::class,
			$query_after_action,
			'New post editor load action should invoke WooCommerce includes.'
		);
		$this->assertTrue(
			function_exists( 'wc_set_notices' ),
			'New post editor load action should load frontend includes such as wc-notice-functions.php.'
		);
	}
}
