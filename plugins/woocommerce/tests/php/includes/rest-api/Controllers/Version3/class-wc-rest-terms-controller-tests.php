<?php

/**
 * class WC_REST_Products_Controller_Tests.
 * Terms Controller tests for V3 REST API.
 */
class WC_REST_Terms_Controller_Tests extends WC_Unit_Test_Case {

	/**
	 * Runs before any test.
	 */
	public function setUp(): void {
		parent::setUp();

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

	/**
	 * @testdox 'get_collection_params' passes params and the taxonomy object through the filter.
	 */
	public function test_get_collection_params_is_filterable() {
		$received = null;

		add_filter(
			'rest_product_cat_collection_params',
			function ( $params, $taxonomy ) use ( &$received ) {
				$received         = $taxonomy;
				$params['custom'] = array( 'type' => 'string' );
				return $params;
			},
			10,
			2
		);

		$params = ( new WC_REST_Product_Categories_Controller() )->get_collection_params();

		$this->assertArrayHasKey( 'custom', $params );
		$this->assertInstanceOf( WP_Taxonomy::class, $received );
		$this->assertSame( 'product_cat', $received->name );
	}

	/**
	 * @testdox 'get_collection_params' does not filter when the controller has no taxonomy.
	 */
	public function test_get_collection_params_skips_filter_without_taxonomy() {
		$fired = false;

		add_filter(
			'rest__collection_params',
			function ( $params ) use ( &$fired ) {
				$fired = true;
				return $params;
			}
		);

		$this->sut->get_collection_params();

		$this->assertFalse( $fired );
	}
}
