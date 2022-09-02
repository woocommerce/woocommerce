<?php
/**
 * Themes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Themes
 */
class WC_Admin_Tests_API_Themes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/themes';

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

		// Editor does not have theme capabilities.
		$this->editor = $this->factory->user->create(
			array(
				'role' => 'editor',
			)
		);
	}

	/**
	 * Test that an error is thrown if the file is missing.
	 */
	public function test_missing_upload() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_file', $data['code'] );
	}

	/**
	 * Test that a user without capabilities receives a forbidden response error.
	 */
	public function test_capabilities() {
		wp_set_current_user( $this->editor );

		$request  = new WP_REST_Request( 'POST', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cannot_view', $data['code'] );
	}


	/**
	 * Test schema.
	 */
	public function test_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 3, $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'message', $properties );
		$this->assertArrayHasKey( 'theme', $properties );
	}
}
