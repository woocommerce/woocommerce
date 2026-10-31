<?php
declare( strict_types = 1 );

/**
 * class WC_REST_Product_Attributes_Controller_Tests.
 * Product Attributes Controller tests for V3 REST API.
 */
class WC_REST_Product_Attributes_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		// Names other tests don't use: wc_create_attribute() fails if the pa_* taxonomy is already registered.
		foreach ( array( 'Strap Color', 'Lens Size', 'Frame Material' ) as $name ) {
			$this->assertIsInt( wc_create_attribute( array( 'name' => $name ) ), "The {$name} attribute fixture should be created" );
		}
	}

	/**
	 * @testdox Should limit the attributes list to the requested slugs, with or without the "pa_" prefix.
	 *
	 * @testWith ["pa_strap-color", ["pa_strap-color"]]
	 *           ["strap-color", ["pa_strap-color"]]
	 *           ["PA_Strap-Color", ["pa_strap-color"]]
	 *           [["strap-color", "pa_lens-size"], ["pa_lens-size", "pa_strap-color"]]
	 *           [["strap-color", "pa_strap-color"], ["pa_strap-color"]]
	 *           ["does-not-exist", []]
	 *           ["pa_", []]
	 *
	 * @param string|string[] $slug           Value of the slug parameter.
	 * @param string[]        $expected_slugs Slugs expected in the response, in order.
	 */
	public function test_get_items_filters_by_slug( $slug, array $expected_slugs ): void {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/attributes' );
		$request->set_param( 'slug', $slug );

		$response = $this->server->dispatch( $request );
		$headers  = $response->get_headers();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected_slugs, wp_list_pluck( $response->get_data(), 'slug' ), 'The response should only contain the requested attributes' );
		$this->assertEquals( count( $expected_slugs ), $headers['X-WP-Total'], 'X-WP-Total should count the filtered attributes' );
		$this->assertEquals( 1, $headers['X-WP-TotalPages'] );
	}

	/**
	 * @testdox Should return every attribute when the slug parameter is missing or empty.
	 *
	 * @testWith [null]
	 *           [""]
	 *
	 * @param string|null $slug Value of the slug parameter, or null to leave it out.
	 */
	public function test_get_items_returns_all_attributes_without_slug( ?string $slug ): void {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/attributes' );
		if ( null !== $slug ) {
			$request->set_param( 'slug', $slug );
		}

		$response = $this->server->dispatch( $request );
		$slugs    = wp_list_pluck( $response->get_data(), 'slug' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( count( wc_get_attribute_taxonomies() ), $slugs, 'Every attribute should be returned' );
		foreach ( array( 'pa_frame-material', 'pa_lens-size', 'pa_strap-color' ) as $fixture_slug ) {
			$this->assertContains( $fixture_slug, $slugs );
		}
		$this->assertEquals( count( $slugs ), $response->get_headers()['X-WP-Total'] );
	}

	/**
	 * @testdox Should reject a slug parameter that is not a string or a list of strings.
	 */
	public function test_get_items_rejects_invalid_slug_param(): void {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/attributes' );
		$request->set_param( 'slug', array( array( 'strap-color' ) ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}
}
