<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * A collection of tests for the RestApiUtil lazy_load_namespace method.
 */
class RestApiUtilTest extends \WC_Unit_Test_Case {

	/**
	 * The RestApiUtil instance for testing.
	 *
	 * @var RestApiUtil
	 */
	private $rest_api_util;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rest_api_util = new RestApiUtil();

		// Clear any existing filters.
		remove_all_filters( 'woocommerce_rest_should_lazy_load_namespace' );
		remove_all_filters( 'rest_pre_dispatch' );
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clear any filters that may have been added during tests.
		remove_all_filters( 'woocommerce_rest_should_lazy_load_namespace' );
		remove_all_filters( 'rest_pre_dispatch' );

		// Clear global wp query vars.
		if ( isset( $GLOBALS['wp'] ) ) {
			$GLOBALS['wp']->query_vars = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

	/**
	 * @testdox `lazy_load_namespace` should execute callback immediately when lazy loading is disabled via filter.
	 */
	public function test_lazy_load_namespace_executes_callback_immediately_when_disabled() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// Disable lazy loading via filter.
		add_filter( 'woocommerce_rest_should_lazy_load_namespace', '__return_false' );

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		$this->assertTrue( $callback_executed );
	}

	/**
	 * @testdox `lazy_load_namespace` should pass the correct namespace parameter to the filter.
	 */
	public function test_lazy_load_namespace_passes_namespace_to_filter() {
		$filter_namespace = null;
		$callback         = function () {
		};

		add_filter(
			'woocommerce_rest_should_lazy_load_namespace',
			function ( $should_lazy_load, $route_namespace ) use ( &$filter_namespace ) {
				$filter_namespace = $route_namespace;

				return true;
			},
			10,
			2
		);

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		$this->assertEquals( 'wc/v3', $filter_namespace );
	}

	/**
	 * @testdox `lazy_load_namespace` should execute callback when matching route is found.
	 */
	public function test_lazy_load_namespace_executes_callback_on_matching_route() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// Set up matching REST route.
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/v3/products';

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		$this->assertTrue( $callback_executed );
	}

	/**
	 * @testdox `lazy_load_namespace` should not execute callback immediately when route doesn't match.
	 */
	public function test_lazy_load_namespace_defers_callback_on_non_matching_route() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// Set up non-matching REST route.
		$GLOBALS['wp']->query_vars['rest_route'] = '/wp/v2/posts';

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		// Callback should not be executed immediately for non-matching routes.
		$this->assertFalse( $callback_executed );
	}

	/**
	 * @testdox `lazy_load_namespace` should execute callback for root requests.
	 */
	public function test_lazy_load_namespace_executes_callback_for_root_requests() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// Set up root REST route.
		$GLOBALS['wp']->query_vars['rest_route'] = '/';

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		$this->assertTrue( $callback_executed );
	}

	/**
	 * @testdox `lazy_load_namespace` should execute callback for root requests.
	 */
	public function test_lazy_load_namespace_executes_callback_for_namespace_index_requests() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// Set up root REST route.
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/v3/';

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		$this->assertTrue( $callback_executed );
	}

	/**
	 * @testdox `lazy_load_namespace` should handle missing rest_route gracefully.
	 */
	public function test_lazy_load_namespace_handles_missing_rest_route() {
		$callback_executed = false;
		$callback          = function () use ( &$callback_executed ) {
			$callback_executed = true;
		};

		// query_vars should be empty by default.

		$this->rest_api_util->lazy_load_namespace( 'wc/v3', $callback );

		// Should register filter but not execute callback immediately.
		$this->assertFalse( $callback_executed );

		// Verify that rest_pre_dispatch filter was added.
		$this->assertTrue( has_filter( 'rest_pre_dispatch' ) );
	}
}
