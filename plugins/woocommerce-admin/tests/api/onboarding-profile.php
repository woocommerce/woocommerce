<?php
/**
 * Onboarding Profile REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingProfile;

/**
 * WC Tests API Onboarding Profile
 */
class WC_Tests_API_Onboarding_Profiles extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/profile';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test that profile data is returned by the endpoint.
	 */
	public function test_get_profile_items() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$properties = OnboardingProfile::get_profile_properties();
		foreach ( $properties as $key => $property ) {
			$this->assertArrayHasKey( $key, $properties );
		}
	}

	/**
	 * Test that profile date is updated by the endpoint.
	 */
	public function test_update_profile_items() {
		wp_set_current_user( $this->user );

		// Test updating 2 fields separately.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$industry = array(
			array(
				'slug' => 'health-beauty',
			),
		);
		$request->set_body( wp_json_encode( array( 'industry' => $industry ) ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );

		// Test that the update works.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$request->set_body( wp_json_encode( array( 'theme' => 'Storefront' ) ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );

		// Make sure the original field value wasn't overwritten.
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'health-beauty', $data['industry'][0]['slug'] );
		$this->assertEquals( 'storefront', $data['theme'] );
	}

	/**
	 * Test schema.
	 *
	 * @since 3.5.0
	 */
	public function test_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 13, $properties );
		$this->assertArrayHasKey( 'completed', $properties );
		$this->assertArrayHasKey( 'skipped', $properties );
		$this->assertArrayHasKey( 'industry', $properties );
		$this->assertArrayHasKey( 'product_types', $properties );
		$this->assertArrayHasKey( 'product_count', $properties );
		$this->assertArrayHasKey( 'selling_venues', $properties );
		$this->assertArrayHasKey( 'revenue', $properties );
		$this->assertArrayHasKey( 'other_platform', $properties );
		$this->assertArrayHasKey( 'other_platform_name', $properties );
		$this->assertArrayHasKey( 'business_extensions', $properties );
		$this->assertArrayHasKey( 'theme', $properties );
		$this->assertArrayHasKey( 'wccom_connected', $properties );
		$this->assertArrayHasKey( 'setup_client', $properties );
	}

	/**
	 * Test that profiles response changes based on applied filters.
	 */
	public function test_profile_extensibility() {
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_rest_onboarding_profile_properties',
			function( $properties ) {
				$properties['test_profile_datum'] = array(
					'type'        => 'array',
					'description' => __( 'Test onboarding profile extensibility.', 'woocommerce-admin' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				);
				return $properties;
			}
		);

		// Test that the update works.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$request->set_body( wp_json_encode( array( 'test_profile_datum' => 'woo' ) ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );

		// Test that the new field is retrieved.
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'woo', $data['test_profile_datum'] );
	}

	/**
	 * Ensure that every REST controller works with their defaults.
	 */
	public function test_default_params() {
		$endpoints = array(
			'/wc-admin/onboarding/profile',
			'/wc-admin/plugins',
		);

		foreach ( $endpoints as $endpoint ) {
			$request  = new WP_REST_Request( 'GET', $endpoint );
			$response = $this->server->dispatch( $request );

			// Surface any errors for easier debugging.
			if ( is_wp_error( $response ) ) {
				$this->fail( $response->get_error_message() );
			}

			$this->assertTrue( is_array( $response->get_data() ) );
		}
	}
}
