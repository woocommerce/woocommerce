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
	 * @testdox Should not include filter param disallow rules in robots.txt by default.
	 */
	public function test_robots_txt_omits_filter_disallow_rules_by_default(): void {
		$base = "User-agent: *\nDisallow: /wp-admin/\n";

		$output = WC()->robots_txt( $base );

		$this->assertStringNotContainsString( 'Disallow: /*?filter_*', $output, 'robots.txt should not disallow filter params by default' );
		$this->assertStringNotContainsString( 'Disallow: /*?min_price=*', $output, 'robots.txt should not disallow min_price by default' );
	}

	/**
	 * @testdox Should include filter param disallow rules in robots.txt when opted in.
	 */
	public function test_robots_txt_includes_filter_disallow_rules_when_opted_in(): void {
		add_filter( 'woocommerce_disallow_filter_params_in_robots_txt', '__return_true' );
		$base = "User-agent: *\nDisallow: /wp-admin/\n";

		$output = WC()->robots_txt( $base );

		$this->assertStringContainsString( 'Disallow: /*?filter_*', $output );
		$this->assertStringContainsString( 'Disallow: /*?*filter_*', $output );
		$this->assertStringContainsString( 'Disallow: /*?rating_filter=*', $output );
		$this->assertStringContainsString( 'Disallow: /*?*rating_filter=*', $output );
		$this->assertStringContainsString( 'Disallow: /*?min_price=*', $output );
		$this->assertStringContainsString( 'Disallow: /*?*min_price=*', $output );
		$this->assertStringContainsString( 'Disallow: /*?max_price=*', $output );
		$this->assertStringContainsString( 'Disallow: /*?*max_price=*', $output );

		remove_all_filters( 'woocommerce_disallow_filter_params_in_robots_txt' );
	}

	/**
	 * @testdox Should include filter disallow rules inside the User-agent wildcard group when opted in.
	 */
	public function test_robots_txt_filter_rules_inside_wildcard_group(): void {
		add_filter( 'woocommerce_disallow_filter_params_in_robots_txt', '__return_true' );
		$base = "User-agent: *\nDisallow: /wp-admin/\n";

		$output = WC()->robots_txt( $base );
		$lines  = explode( PHP_EOL, $output );

		$agent_index  = array_search( 'User-agent: *', $lines, true );
		$filter_index = array_search( 'Disallow: /*?filter_*', $lines, true );

		$this->assertNotFalse( $agent_index, 'User-agent: * line should exist' );
		$this->assertNotFalse( $filter_index, 'Disallow: /*?filter_* line should exist' );
		$this->assertGreaterThan( $agent_index, $filter_index, 'Filter disallow rule should appear after User-agent: * directive' );

		remove_all_filters( 'woocommerce_disallow_filter_params_in_robots_txt' );
	}
}
