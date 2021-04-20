<?php

/**
 * class WC_REST_Products_Controller_Tests.
 * Terms Controller tests for V3 REST API.
 */
class WC_REST_Terms_Controller_Tests extends WC_Unit_Test_Case {

	/**
	 * Runs before any test.
	 */
	public function setUp() {
		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_REST_Terms_Controller {
			public function get_taxonomy( $request ) {
				return parent::get_taxonomy( $request );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting
	}

	/**
	 * @testdox 'get_taxonomy' returns the proper values when called for different requests.
	 */
	public function test_get_taxonomy_returns_the_proper_values_for_different_requests() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_attribute_taxonomy_name_by_id' => function( $attribute_id ) {
					return 'taxonomy_' . $attribute_id;
				},
			)
		);

		$request = array( 'attribute_id' => 1 );
		$value1  = $this->sut->get_taxonomy( $request );

		$request = array( 'attribute_id' => 2 );
		$value2  = $this->sut->get_taxonomy( $request );

		$this->assertEquals( 'taxonomy_1', $value1 );
		$this->assertEquals( 'taxonomy_2', $value2 );
	}
}
