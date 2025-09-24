<?php
declare( strict_types = 1);

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch -- backcompat nomenclature.

/**
 * Tests for wc-rest-functions.php.
 * Class WC_Rest_Functions_Test.
 */
class WCRestFunctionsTest extends WC_REST_Unit_Test_Case {

	/**
	 * Set up test environment before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$GLOBALS['wp']             = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp']->query_vars = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset( $GLOBALS['wp'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testDox All namespaces are loaded for unknown path.
	 */
	public function test_wc_rest_should_load_namespace_unknown() {
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v1', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-analytics', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-telemetry', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-random', 'wc/unknown' ) );
	}

	/**
	 * @testDox Only required namespace is loaded for known path.
	 */
	public function test_wc_rest_should_load_namespace_known() {
		$this->assertFalse( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		$this->assertFalse( wc_rest_should_load_namespace( 'wc-analytics', 'wc/v2' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v2', 'wc/v2' ) );
	}

	/**
	 * @testDox Test wc_rest_should_load_namespace known works with preload.
	 */
	public function test_wc_rest_should_load_namespace_known_works_with_preload() {
		$memo = rest_preload_api_request( array(), '/wc/store/v1/cart' );
		$this->assertArrayHasKey( '/wc/store/v1/cart', $memo );
	}

	/**
	 * @testDox Test wc_rest_should_load_namespace filter.
	 */
	public function test_wc_rest_should_load_namespace_filter() {
		$this->assertFalse( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		add_filter( 'wc_rest_should_load_namespace', '__return_true' );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		remove_filter( 'wc_rest_should_load_namespace', '__return_true' );
	}

	/**
	 * Test namespace loading when route matches exactly
	 */
	public function test_loads_namespace_when_route_matches_exactly() {
		$callback_called = false;
		$test_callback   = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		$GLOBALS['wp']->query_vars['rest_route'] = 'wc/wc-rest-testing/products';

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback );

		$this->assertTrue( $callback_called, 'Callback should be executed when route matches namespace' );
	}

	/**
	 * Test namespace loading for root route (API discovery)
	 */
	public function test_loads_namespace_for_root_route() {
		$callback_called = false;
		$test_callback   = function () use ( &$callback_called ) {
			$callback_called = true;
		};
		$GLOBALS['wp']->query_vars['rest_route'] = '/';

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback );
		$this->assertTrue( $callback_called, 'Callback should be executed for root route to maintain API discovery' );
	}

	/**
	 * Test that callback is not called when route doesn't match
	 */
	public function test_does_not_load_namespace_when_route_doesnt_match() {
		$callback_called = false;
		$test_callback   = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		$GLOBALS['wp']->query_vars['rest_route'] = 'wc/some-other-namespace';

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback  );
		$this->assertFalse( $callback_called, 'Callback should not be executed when route doesn\'t match' );
	}

	/**
	 * Test retrieval of REST route from globals when not provided
	 */
	public function test_retrieves_route_from_globals_when_not_provided() {
		$callback_called = false;
		$test_callback   = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		$GLOBALS['wp']->query_vars['rest_route'] = 'wc/wc-rest-testing/products';

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback );
		$this->assertTrue( $callback_called, 'Should retrieve route from globals and execute callback' );
	}

	/**
	 * Test handling of empty REST route
	 */
	public function test_handles_empty_rest_route_gracefully() {
		$callback_called = false;
		$test_callback   = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		$GLOBALS['wp']->query_vars['rest_route'] = '';

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback );
		$this->assertFalse( $callback_called, 'Should not execute callback when REST route is empty' );
	}

	/**
	 * Test that multiple requests do not trigger loading a namespace multiple times.
	 */
	public function test_removes_filter_to_prevent_recursion() {
		$callback_called_times = 0;
		$test_callback         = function () use ( &$callback_called_times ) {
			++$callback_called_times;
		};

		wc_rest_lazy_load_namespace( 'wc/wc-rest-testing', $test_callback );
		$this->assertEquals( 0, $callback_called_times, 'Callback should not be executed' );

		$request = new WP_REST_Request( 'GET', '/wc/wc-rest-testing/products/' );
		$this->server->dispatch( $request );
		$this->assertEquals( 1, $callback_called_times, 'Callback should have been executed exactly once' );

		$this->server->dispatch( $request );
		$this->assertEquals( 1, $callback_called_times, 'Callback should have only been executed once' );
	}
}
