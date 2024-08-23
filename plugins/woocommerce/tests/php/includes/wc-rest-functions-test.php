<?php
declare( strict_types = 1);

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch -- backcompat nomenclature.

/**
 * Tests for wc-rest-functions.php.
 * Class WC_Rest_Functions_Test.
 */
class WCRestFunctionsTest extends WC_Unit_Test_Case {

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
}
